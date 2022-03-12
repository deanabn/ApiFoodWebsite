<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\RecipeCategoriesModel;
use App\Helpers\ResponseHelper;

class RecipeCategories extends ResourceController
{
    use ResponseTrait;

    public function __construct()
    {
        $this->mrecipe_categories = new RecipeCategoriesModel();
        $this->respondHelper = new ResponseHelper();
    }

    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        $result = $this->mrecipe_categories->findAll();
        $respond = $this->respondHelper->generateRespond($result, 200);
        return $this->respond($respond, 200);
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        $checkId = $this->mrecipe_categories->find(['id' => $id]);
        $respond = $this->respondHelper->generateRespond($checkId, 404);
        if (!$checkId) return $this->respond($respond, 404);

        $respond = $this->respondHelper->generateRespond($checkId, 200);
        return $this->respond($respond, 200);
    }

    /**
     * Return a new resource object, with default properties
     *
     * @return mixed
     */
    public function new()
    {
        //
    }

    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        if (!$this->request->getVar('name')) {
            $respond = $this->respondHelper->generateRespond("", 404);
            return  $this->respond($respond, 404);
        }

        $data = [
            'name' => $this->request->getVar('name')
        ];

        if ($this->mrecipe_categories->save($data)){
            $data = $this->mrecipe_categories->find(['id' => $this->mrecipe_categories->getInsertID()]);
            $respond = $this->respondHelper->generateRespond($data, 200);
            return $this->respondCreated($respond);
        }
        return $this->respond("Error", 500);
    }

    /**
     * Return the editable properties of a resource object
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Add or update a model resource, from "posted" properties
     *
     * @return mixed
     */
    public function update($id = null)
    {
        helper(['form']);
        $rules = [
            'name' => 'required'
        ];
        if (!$this->validate($rules)) {
            $respond = $this->respondHelper->generateRespond($this->validator->getErrors(), 400);
            return  $this->respond($respond, 400);
        }else{
            $data = [
                'name' => $this->request->getVar('name')
            ];

            $checkId = $this->mrecipe_categories->find(['id' => $id]);
            $respond = $this->respondHelper->generateRespond($checkId, 404);
            if (!$checkId) return $this->respond($respond, 404);

            $updated = $this->mrecipe_categories->update($id, $data);
            if (!$updated) return $this->fail('Gagal Update', 400);

            $respond = $this->respondHelper->generateRespond($this->mrecipe_categories->find(['id' => $id]), 200);
            return $this->respond($respond, 200);
        }
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        $checkId = $this->mrecipe_categories->find(['id' => $id]);
        $respond = $this->respondHelper->generateRespond($checkId, 404);
        if (!$checkId) return $this->respond($respond, 404);
        
        try {
            $deleted = $this->mrecipe_categories->delete(['id' => $id]);
        } catch (\Exception $e) {
            $respond = $this->respondHelper->generateRespond($e->getMessage(), 400);
            return $this->respond($respond, 400);
        }

        $respond = $this->respondHelper->generateRespond($this->mrecipe_categories->find(['id' => $id]), 200);
        return $this->respond($respond, 200);
    }
}
