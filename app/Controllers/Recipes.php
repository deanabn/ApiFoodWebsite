<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\RecipesModel;
use App\Models\RecipeCategoriesModel;
use App\Helpers\ResponseHelper;

class Recipes extends ResourceController
{
    use ResponseTrait;

    public function __construct()
    {
        $this->mrecipes = new RecipesModel();
        $this->mrecipe_categories = new RecipeCategoriesModel();
        $this->respondHelper = new ResponseHelper();

        $this->db = \Config\Database::connect();
        $this->mingredients_lines = $this->db->table('ingredients_lines');
        $this->msteps_lines = $this->db->table('steps_lines');
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
            $data['total'] = $this->mrecipes->getRecipes($params, true);
            $data['recipes'] = $this->mrecipes->getRecipes($params);
            $respondCode = 200;

            foreach ($data['recipes'] as $key => $value) {
                $data['recipes'][$key]['recipeCategory'] = $this->mrecipe_categories->find(['id' => $value['recipeCategoryId']])[0];
            }
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
    public function show($id = null, $steps = "")
    {
        $dataRecipe = $this->mrecipes->find(['id' => $id]);
        $respond = $this->respondHelper->generateRespond($dataRecipe, 404);
        if (!$dataRecipe) return $this->respond($respond, 404);

        $nServing = $this->request->getVar('nServing');
        if ($nServing == null) {
            $nServing = 1;
        }

        if ($nServing < 1) {
            $respond = $this->respondHelper->generateRespond("Target serving minimum 1", 400);
            return  $this->respond($respond, 400);
        }else{
            if ($steps === "steps") {
                $dataSteps = $this->mrecipes->getSteps($id);
                $respond = $this->respondHelper->generateRespond($dataSteps, 200);
                return  $this->respond($respond, 200);
            }
            $dataRecipe[0]['ingredientsPerServing'] = $this->mrecipes->getIngredientsServing($id, $nServing);
            $respond = $this->respondHelper->generateRespond($dataRecipe[0], 200);
            return  $this->respond($respond, 200);
        }

        
        // $dataRecipe = $this->mrecipes->find(['id' => $id])[0];
        // $dataRecipe['ingredientsPerServing'] = $this->mingredients_lines->getWhere(['recipe_id' => $id])->getResultArray();
        // $dataRecipe['steps'] = $this->msteps_lines->getWhere(['recipe_id' => $id])->getResultArray();
        // $respond = $this->respondHelper->generateRespond($dataRecipe, 200);
        // return  $this->respond($respond, 200);
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
        // return $this->respond($this->db->query("select * from recipe_category")->getResult(), 200);
        helper(['form']);
        $rules = [
            'name' => 'required',
            'recipeCategoryId' => 'required',
            'image' => 'required',
            'nServing' => 'required',
            "ingredientsPerServing" => 'required',
            "steps" => 'required'
        ];
        if (!$this->validate($rules)) {
            $respond = $this->respondHelper->generateRespond($this->validator->getErrors(), 400);
            return  $this->respond($respond, 400);
        }

        if (!$this->request->getVar()) {
            $respond = $this->respondHelper->generateRespond("", 404);
            return  $this->respond($respond, 404);
        }

        $dataRecipe = [
            'name' => $this->request->getVar('name'),
            'recipeCategoryId' => $this->request->getVar('recipeCategoryId'),
            'image' => $this->request->getVar('image'),
            'nServing' => $this->request->getVar('nServing'),
        ];
        $dataIngredients = $this->request->getVar('ingredientsPerServing');
        $dataSteps = $this->request->getVar('steps');
        
        $this->db->transStart();

        if ($this->mrecipes->save($dataRecipe)){
            $newRecipeId = $this->mrecipes->getInsertID();
            foreach ($dataIngredients as $ing) {
                $ing->recipe_id = $newRecipeId;
                $this->mingredients_lines->insert( (array) $ing);
            }
            foreach ($dataSteps as $step) {
                $step->recipe_id = $newRecipeId;
                $this->msteps_lines->insert( (array) $step);
            }

            $data = $this->mrecipes->find(['id' => $newRecipeId]);
            $respond = $this->respondHelper->generateRespond($data, 200);

            $this->db->transComplete();

            return $this->respondCreated($respond);
        }

        $this->db->transComplete();
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
            'name' => 'required',
            'recipeCategoryId' => 'required',
            'image' => 'required',
            'nServing' => 'required',
            "ingredientsPerServing" => 'required',
            "steps" => 'required'
        ];
        if (!$this->validate($rules)) {
            $respond = $this->respondHelper->generateRespond($this->validator->getErrors(), 400);
            return  $this->respond($respond, 400);
        }

        $dataRecipe = [
            'name' => $this->request->getVar('name'),
            'recipeCategoryId' => $this->request->getVar('recipeCategoryId'),
            'image' => $this->request->getVar('image'),
            'nServing' => $this->request->getVar('nServing'),
        ];
        $dataIngredients = $this->request->getVar('ingredientsPerServing');
        $dataSteps = $this->request->getVar('steps');

        $checkId = $this->mrecipes->find(['id' => $id]);
        $respond = $this->respondHelper->generateRespond($checkId, 404);
        if (!$checkId) return $this->respond($respond, 404);
        
        $this->db->transStart();

        if ($this->mrecipes->update($id, $dataRecipe)){
            $this->mingredients_lines->delete(['recipe_id' => $id]);
            $this->msteps_lines->delete(['recipe_id' => $id]);
            foreach ($dataIngredients as $ing) {
                $ing->recipe_id = $id;
                $this->mingredients_lines->insert( (array) $ing);
            }
            foreach ($dataSteps as $step) {
                $step->recipe_id = $id;
                $this->msteps_lines->insert( (array) $step);
            }

            // $data = $this->mrecipes->find(['id' => $id]);
            // $respond = $this->respondHelper->generateRespond($data, 200);

            $this->db->transComplete();

            return $this->show($id);
        }

        $this->db->transComplete();
        return $this->respond("Error", 500);
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        $checkId = $this->mrecipes->find(['id' => $id]);
        $respond = $this->respondHelper->generateRespond($checkId, 404);
        if (!$checkId) return $this->respond($respond, 404);

        if ($this->mrecipes->delete(['id' => $id])){
            $data = $this->mrecipes->find(['id' => $id]);
            $respond = $this->respondHelper->generateRespond($data, 200);
            return $this->respond($respond, 200);
        }

        return $this->respond("Error", 500);
    }
}
