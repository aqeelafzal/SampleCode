<?php
// Members Model
class Member extends AppModel {

	var $name = 'Member';
	var $validate = array(
		'name' => array('notempty'),
		'email' => array('email'),
		'password' => array('notempty'),
		'address' => array('notempty'),
		'phone' => array('numeric'),
		'image' => array('notempty')
	);

	var $hasOne = array(
		'Order' => array(
			'className' => 'Order',
			'foreignKey' => 'member_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

	var $hasMany = array(
		'Order' => array(
			'className' => 'Order',
			'foreignKey' => 'member_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

	// Confirm Member's password
	function confirmPassword($data) {
        $valid = false;
       
        if ( !empty($data['Member']['password']) && $data['Member']['password'] == Security::hash(Configure::read('Security.salt') . $data['Member']['password_confirm'])) {
            $valid = true;
        }
       
        return $valid;
    }
	
	// Check if old password matches
	function checkPassword($data, $id) {
		
        $valid = false;

		$this->unbindModel(array('hasMany'=>array('Order'),'hasOne'=>array('Order')),false);

	   $params = array(
			  'fields' => 'Member.password',
			  'conditions' => array('Member.id' => $id)
		   );
		
	   $oldPassword = $this->find('all',  $params);
	   $oldPassword = $oldPassword[0]['Member']['password'];
	   
        if ($oldPassword == Security::hash(Configure::read('Security.salt') . $data['Member']['old_password'])) {
            $valid = true;
        }
       
        return $valid;
    }


	// Change Members status
	// Members can be disabled to enabled
	function changeStatus( $id ) 
	{
		$this->unbindModel(array('hasOne'=>array('Order'), 'hasMany'=>array('Order')),false);
		$this->updateAll(array('Member.status' => '!(Member.status)'), array('Member.id' => $id));
	}
	
	// Check Members Email
	function checkEmail($email)
	{
		$this->unbindModel(array('hasOne'=>array('Order'), 'hasMany'=>array('Order')),false);
		
		$members = $this->find('count', array('conditions' => array('Member.email' => $email)));
		
		if ( $members > 0 )
			return true;
		else
			return false;
	}

}
?>