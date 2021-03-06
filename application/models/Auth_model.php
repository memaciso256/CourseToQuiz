<?php
class Auth_model extends CI_Model{
   function __construct(){
     parent::__construct();
   }


   // Normal Login
    function isLoginValid($username,$password){
       $q = $this->db->select('*')
                ->from('user')
                ->group_start()
                ->where('username',$username)
                ->or_where('email',$username)
                ->group_end()
                ->where('password',$password)
                ->where('status',1)
                ->get();
        if($q->num_rows() == 1)
          return true;
    }


    // Get Login details
    function getLoginDetail($username,$password){
      $q = $this->db->select('user.*,userDetail.first_name,userDetail.last_name')
                    ->from('user')
                    ->join('userDetail','user.id=userDetail.user_id','left')
                    ->where([ 'email' => $username , 'password' => $password ])
                    ->get();
      if($q->num_rows() == 1)
        return $q->result_array();
    }


    // User Registration
    function register($data,$data2){
      $this->db->insert('user',$data);
      $data2['user_id'] = $this->db->insert_id();
      $this->db->insert('userDetail',$data2);
      return true;
    }


    function currentUserPassword($userId,$encryptedPassword){
       $query = $this->db->where(['id' => $userId,'password' =>$encryptedPassword ])->get('user');
       if($query->num_rows() > 0){
         return true;
       }
    }


    public function isUsernameAvailable($username){
       $query = $this->db->where('username',$username)->get('user');
       return ($query->num_rows() > 0) ? true : false;
    }


    public function isEmailAvailable($email){
       $query = $this->db->where('email',$email)->get('user');
       return ($query->num_rows() > 0) ? true : false;
    }

    public function setPasswordRequestCode($rand,$email){
      $this->db->where('email',$email);
      $this->db->update('user',['resetCode' => $rand]);
      return true;
    }

    public function checkPasswordRequestCode($code,$email){
      $query = $this->db->where(['email' => $email,'resetCode' => $code])->get('user');
      return ($query->num_rows() > 0) ? true : false;
    }

    public function changePassword($email,$newPassword){
      $this->db->where("email",$email);
      $this->db->update('user',["password" => md5($newPassword),"resetCode" => NULL]);
      return true;
    }

    // Get SocialOAuth Info
    function getSocialOAuth($where){
      $query = $this->db->select('user.*,userDetail.first_name,userDetail.last_name')
                    ->from('user')
                    ->join('userDetail','user.id=userDetail.user_id','left')
                    ->where($where)
                    ->get();
      return @$query->result_array()[0];
    }

    // User facebookOAuth
    function facebookOAuth($data){
      $query = $this->getSocialOAuth([
        'user.facebookOAuth' => json_encode($data,TRUE)
      ]);
      if(is_array($query)){
         return $query;
      }else{
        if($this->isEmailAvailable($data['email']) == 1){
           $this->db->where('email',$data['email']);
           $this->db->update('user',['facebookOAuth' => json_encode($data,TRUE)]);
        }else{
          $this->register([
            'username' => $data['email'],
            'email'    => $data['email'],
            'facebookOAuth' => json_encode($data,true)
          ],[
            'first_name' => explode(' ',$data['name'])[0],
            'last_name'  => explode(' ',$data['name'])[1],
          ]);
        }
         return $this->getSocialOAuth([
           'user.facebookOAuth' => json_encode($data,TRUE)
         ]);
       }
    }


}
 ?>
