<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Helpers\ResponseHelper;
use App\Models\RecipesModel;
use App\Models\ServeHistoriesModel;
use App\Controllers\Auth;

class ServeHistories extends ResourceController
{
    use ResponseTrait;

    public function __construct()
    {
        // $this->mrecipes = new RecipesModel();
        $this->respondHelper = new ResponseHelper();
        $this->mrecipes = new RecipesModel();
        $this->mserve = new ServeHistoriesModel();
        $this->auth = new Auth();
    }

    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        $params = $this->request->getVar();
        
        try {
            $data['total'] = count($this->mserve->getListHistory($params, true));
            $data['history'] = $this->mserve->getListHistory($params);
            $respondCode = 200;
        } catch (\Exception $e) {
            $data = $e->getMessage();
            $respondCode = 500;
        }
        
        $respond = $this->respondHelper->generateRespond($data, $respondCode);
        return  $this->respond($respond, $respondCode);
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        $dataServe = $this->mserve->getServebyId($id);
        $respond = $this->respondHelper->generateRespond($dataServe, 404);
        if (!$dataServe) return $this->respond($respond, 404);

        $dataSteps = $this->mrecipes->getSteps($dataServe[0]['recipeId']);
        for ($i=0; $i < $dataServe[0]['nStep']; $i++) {
            if ($i < $dataServe[0]['nStepDone']) {
                $dataSteps[$i]['done'] = true;
            }else{
                $dataSteps[$i]['done'] = false;
            }
        }
        $dataServe[0]['steps'] = $dataSteps;
        $respond = $this->respondHelper->generateRespond($dataServe[0], 200);
        return  $this->respond($respond, 200);
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
        helper(['form']);
        $rules = [
            'nServing' => 'required|greater_than[0]',
            'recipeId' => 'required',
        ];
        if (!$this->validate($rules)) {
            foreach ($this->validator->getErrors() as $key => $value) {
                $respond = $this->respondHelper->generateRespond($value, 400);
                return  $this->respond($respond, 400);
            }
        }

        $dataRecipe = $this->mrecipes->find(['id' => $this->request->getVar('recipeId')]);
        $respond = $this->respondHelper->generateRespond($dataRecipe, 404);
        if (!$dataRecipe) return $this->respond($respond, 404);

        $id = strtoupper(substr(str_shuffle(str_repeat("abcdefghijklmnopqrstuvwxyz", 2)), 0, 2)) . substr(str_shuffle(str_repeat("0123456789", 2)), 0, 2);
        $user = $this->auth->get_user($this->request->getServer('HTTP_AUTHORIZATION'));
        $dataServe = [
            'id' => $id,
            'userId' => $user->uid,
            'nServing' => $this->request->getVar('nServing'),
            'recipeId' => $this->request->getVar('recipeId'),
            'nStep' => count($this->mrecipes->getSteps($this->request->getVar('recipeId'))),
            'nStepDone' => 1,
            'reaction' => null,
            'status' => 'progress',
        ];
        $saved = $this->mserve->insert($dataServe);
        // return $this->respond($saved, 404);
        return $this->show($id);
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
    public function update($id = null, $extraPath = '')
    {
        $dataServe = $this->mserve->find(['id' => $id]);
        $respond = $this->respondHelper->generateRespond($dataServe, 404);
        if (!$dataServe) return $this->respond($respond, 404);

        $respond = $this->respondHelper->generateRespond('Already Done', 400);
        if ($dataServe[0]['status'] != 'progress') return $this->respond($respond, 400);

        $stepInput = $this->request->getVar('stepOrder');
        $stepNow = $dataServe[0]['nStepDone'];
        $stepTotal = $dataServe[0]['nStep'];

        $respond = $this->respondHelper->generateRespond("stepOrder is Required", 400);
        if (!$stepInput) return $this->respond($respond, 400);

        $respond = $this->respondHelper->generateRespond("Cannot Skip Step", 400);
        if ($stepInput - 1 != $stepNow) return $this->respond($respond, 400);

        if ($stepInput == $stepTotal) {
            $this->mserve->update($id, ['nStepDone' => $stepInput, 'status' => 'need-rating']);
        }elseif ($stepInput < $stepTotal) {
            $this->mserve->update($id, ['nStepDone' => $stepInput]);
        }
        
        return $this->show($id);
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        helper(['form']);
        $rules = [
            'id' => 'required',
            'reaction' => 'required|in_list[like,neutral,dislike]',
        ];
        if (!$this->validate($rules)) {
            foreach ($this->validator->getErrors() as $key => $value) {
                $respond = $this->respondHelper->generateRespond($value, 400);
                return  $this->respond($respond, 400);
            }
        }
    }

    public function addReaction($id = null)
    {
        // if (!$extraPath == "reaction") {
        //     $respond = $this->respondHelper->generateRespond($extraPath, 404);
        //     return $this->respond($respond, 404);
        // }

        helper(['form']);
        $rules = [
            'reaction' => 'required|in_list[like,neutral,dislike]',
        ];
        if (!$this->validate($rules)) {
            foreach ($this->validator->getErrors() as $key => $value) {
                $respond = $this->respondHelper->generateRespond($value, 400);
                return  $this->respond($respond, 400);
            }
        }

        $dataServe = $this->mserve->find(['id' => $id]);
        $respond = $this->respondHelper->generateRespond($dataServe, 404);
        if (!$dataServe) return $this->respond($respond, 404);

        $respond = $this->respondHelper->generateRespond('Already Done', 400);
        if ($dataServe[0]['status'] != 'need-rating') return $this->respond($respond, 400);

        $reaction = $this->request->getVar('reaction');
        $this->mserve->update($id, ['reaction' => $reaction, 'status' => 'done']);
        
        return $this->show($id);
    }
}
