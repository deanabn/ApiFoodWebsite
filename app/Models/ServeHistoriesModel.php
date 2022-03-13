<?php

namespace App\Models;

use CodeIgniter\Model;

class ServeHistoriesModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'serve_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['id','userId','nServing','recipeId','nStep','nStepDone','reaction','status','createdAt','updatedAt'];

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

    public function getListHistory($param = array(), $isCount = false){
        $result = $this;
        $limit = 10;
        $offset = 0;
        $sort = ' ORDER BY sh.createdAt DESC ';
        $q = '';
        $category = '';
        $status = '';

        if (array_key_exists("q",$param)) {
            $q = $param['q'];
        }
        if (array_key_exists("categoryId",$param)) {
            $category = " AND recipeCategoryId = " . $param['categoryId'];
        }
        if (array_key_exists("sort",$param)) {
            if ($param['sort'] == "newest") {
                $sort = " ORDER BY sh.createdAt DESC ";
            }elseif ($param['sort'] == "oldest") {
                $sort = " ORDER BY sh.createdAt ASC ";
            }elseif ($param['sort'] == "nserve_asc") {
                $sort = " ORDER BY sh.nServing ASC ";
            }else{
                $sort = " ORDER BY sh.nServing DESC ";
            }
        }

        if (array_key_exists("limit",$param)) {
            $limit = $param['limit'];
        }
        if (array_key_exists("skip",$param)) {
            $offset = $param['skip'];
        }
        if (array_key_exists("status",$param)) {
            $status = " AND `status` = '" . $param['status'] . "' ";
        }

        $sql = "SELECT 
            sh.id,
            sh.nServing,
            reaction,
            `status`,
            recipeId,
            r.name AS recipeName,
            rc.name AS recipeCategoryName,
            r.image AS recipeImage,
            nStep,
            nStepDone,
            sh.createdAt,
            sh.updatedAt
        FROM serve_history sh
        LEFT JOIN recipes r on r.id = sh.recipeId
        LEFT JOIN recipe_category rc on rc.id = r.recipeCategoryId
        WHERE 
            r.name LIKE '%$q%'
            $category
            $status
            $sort";

        if (!$isCount) {
            $sql = $sql . " LIMIT $limit OFFSET $offset ";
            return $this->db->query($sql)->getResultArray();
        }else{
            return $this->db->query($sql)->getResultArray();
        }
    }

    public function getServebyId($id){
        $query = $this->db->query(
            "SELECT 
                sh.id,
                userId,
                sh.nServing,
                reaction,
                `status`,
                recipeId,
                r.recipeCategoryId,
                r.name AS recipeName,
                rc.name AS recipeCategoryName,
                r.image AS recipeImage,
                null steps,
                nStep,
                nStepDone,
                sh.createdAt,
                sh.updatedAt
            FROM serve_history sh
            LEFT JOIN recipes r on r.id = sh.recipeId
            LEFT JOIN recipe_category rc on rc.id = r.recipeCategoryId
            WHERE 
                sh.id = '$id'"
        );
        return $query->getResultArray();
    }
}
