<?php

namespace App\Models;

use CodeIgniter\Model;

class RecipesModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'recipes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['id','name','recipeCategoryId','image','nServing'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function getRecipes($param = array(), $isCount = false){
        $result = $this;
        $limit = 10;
        $offset = 0;

        if (!$param) {
            if (!$isCount) {
                return $this->orderBy('createdAt', 'desc')->findAll($limit,$offset);
            }else{
                return $this->countAllResults();
            }
            
        }

        if (array_key_exists("q",$param)) {
            $result = $this->like('name', $param['q']);
        }
        if (array_key_exists("categoryId",$param)) {
            $result = $this->where('recipeCategoryId', $param['categoryId']);
        }
        if (array_key_exists("sort",$param)) {
            if ($param['sort'] == "name_asc") {
                $result = $this->orderBy('name', 'asc');
            }elseif ($param['sort'] == "name_desc") {
                $result = $this->orderBy('name', 'desc');
            }elseif ($param['sort'] == "like_desc") {
                $result = $this->orderBy('nReactionLike', 'desc');
            }else{
                $result = $this->orderBy('createdAt', 'desc');
            }
        }else{
            $result = $this->orderBy('createdAt', 'desc');
        }

        if (array_key_exists("limit",$param)) {
            $limit = $param['limit'];
        }
        if (array_key_exists("skip",$param)) {
            $offset = $param['skip'];
        }

        if (!$isCount) {
            return $result->findAll($limit,$offset);
        }else{
            return $result->countAllResults();
        } 
    }

    public function getIngredientsServing($recipeId, $nServing = 1){
        $query = $this->db->query(
            "SELECT 
                item,
                unit,
                (value * $nServing) AS 'value'
            FROM ingredients_lines
            WHERE recipe_id = $recipeId"
        );
        return $query->getResultArray();
    }

    public function getSteps($recipeId){
        $query = $this->db->query(
            "SELECT 
                stepOrder,
                description
            FROM steps_lines
            WHERE recipe_id = $recipeId"
        );
        return $query->getResultArray();
    }

    public function getRecipeLike($q,$limit){
        $query = $this->db->query(
            "SELECT 
                id,
                name
            FROM recipes
            WHERE name like '%$q%'
            LIMIT $limit"
        );
        return $query->getResultArray();
    }
}
