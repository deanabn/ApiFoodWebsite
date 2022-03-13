<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Helpers\ResponseHelper;
use App\Models\UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth extends ResourceController
{
    use ResponseTrait;

    public function __construct()
    {
        // $this->mrecipes = new RecipesModel();
        $this->respondHelper = new ResponseHelper();
        $this->muser = new UserModel();
        $this->throttler = \Config\Services::throttler();

        $this->db = \Config\Database::connect();
        $this->attempt = $this->db->table('login_attempt');
    }

    public function get_user($header)
    {
        $key = getenv('TOKEN_KEY');
        
        if (!$header) return "Token Required";
        
        $token = explode(' ', $header)[1];

        try {
            $decoded =  JWT::decode($token, new Key($key, 'HS256'));
            return $decoded;
        } catch (\Exception $th) {
            return $th;
        }
    }

    public function register()
    {
        helper(['form']);
        // $listUsername = ["developer","developer2"];
        $rules = [
            'username' => 'required|is_unique[users.username]',
            'password' => 'required|min_length[6]',
        ];
        if (!$this->validate($rules)) {
            foreach ($this->validator->getErrors() as $key => $value) {
                $respond = $this->respondHelper->generateRespond($value, 400);
                return  $this->respond($respond, 400);
            }
        }

        $data = [
            'username' => $this->request->getVar('username'),
            'password' => password_hash($this->request->getVar('password'), PASSWORD_BCRYPT)
        ];

        
        $registered = $this->muser->save($data);
        $id = $this->muser->getInsertID();

        $dataRespond = [
            'id' => $id,
            'username' => $this->request->getVar('username')
        ];
        $respond = $this->respondHelper->generateRespond($dataRespond, 201);
        return  $this->respond($respond, 201);
    }

    public function login()
    {
        helper(['form']);
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];
        if (!$this->validate($rules)) {
            foreach ($this->validator->getErrors() as $key => $value) {
                $respond = $this->respondHelper->generateRespond($value, 400);
                return  $this->respond($respond, 400);
            }
        }
        $username = $this->request->getVar('username');

        $user = $this->muser->find(['username' => $this->request->getVar('username')]);
        if (!$user) {
            $allow = $this->checkAttempts($username);
            if (!$allow) {
                $respond = $this->respondHelper->generateRespond('Too many invalid login, please wait for 1 minute', 403);
                return  $this->respond($respond, 403);
            }

            $respond = $this->respondHelper->generateRespond('Invalid username or Password', 401);
            return  $this->respond($respond, 401);
        }
        $verify = password_verify($this->request->getVar('password'), $user[0]['password']);
        if (!$verify) {
            $allow = $this->checkAttempts($username);
            if (!$allow) {
                $respond = $this->respondHelper->generateRespond('Too many invalid login, please wait for 1 minute', 403);
                return  $this->respond($respond, 403);
            }

            $respond = $this->respondHelper->generateRespond('Invalid username or Password', 401);
            return  $this->respond($respond, 401);
        }

        $key = getenv('TOKEN_KEY');
        $payload = array(
            "iat"       => 1356999524,
            "nbf"       => 1357000000,
            "uid"       => $user[0]["id"],
            "username"  => $user[0]["username"]
        );

        $data['token'] = JWT::encode($payload, $key, 'HS256');
        // Reset attempt when success login
        $this->attempt->update(['attempt' => 1, 'delay_until' => null ] ,['username' => $username]);

        $respond = $this->respondHelper->generateRespond($data, 200);
        return  $this->respond($respond, 200);
    }

    public function checkAttempts($username)
    {
        date_default_timezone_set('Asia/Jakarta');
        $attempt = $this->attempt->getWhere(['username' => $username])->getResultArray();
        if (!$attempt) {
            $dataAttempt = [
                'username' => $username,
                'attempt' => 0,
                'delay_until' => null
            ];
            $this->attempt->insert($dataAttempt);
        }else{
            $attempt = $attempt[0];
            if ($attempt['attempt']+1 == 3) {
                $this->attempt->update(['attempt' => $attempt['attempt']+1, 'delay_until' => date("Y-m-d h:i:s", strtotime("+1 minutes")) ] ,['username' => $username]);
            }elseif ($attempt['attempt']+1 > 3) {
                if ($attempt['delay_until'] < date("Y-m-d h:i:s")) {
                    $this->attempt->update(['attempt' => 0, 'delay_until' => null ] ,['username' => $username]);
                    return true;
                }
                return false;
                // $respond = $this->respondHelper->generateRespond('Too many invalid login, please wait for 1 minute', 403);
                // return  $this->respond($respond, 403);
            }else{
                $this->attempt->update([ 'attempt' => $attempt['attempt']+1 ] ,['username' => $username]);
            }
        }

        return true;
    }
    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        //
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        //
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
        //
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }
}
