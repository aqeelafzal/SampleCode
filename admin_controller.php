<?php

// Admin Part of the Site
class AdminController extends AppController {

	// Models
	var $uses = array('Admin','Configuration','Album','Photo','Article','Track','Member','Artist','Order');
	// Helpers
	var $helpers = array('Html', 'Form','Javascript','Thumbnail','Getid3','Flash');
	// Compnnents
	var $components = array("Image","Auth");
	
	function index() 
	{
		$this->set('title_for_layout','Home');
	}

	//////////////////////////////////////  Account  ///////////////////////////////
	// Admin user account page
	function account() 
	{
		if (!empty($this->data)) 
		{
			$this->Admin->create();
			$this->Configuration->create();
			
			$this->Configuration->updateAll(
				array('Configuration.value' => "'".$this->data['Admin']['email']."'"),
				array('Configuration.c_key' => 'ADMIN_EMAIL')
			);
			
			$oldPassword = $this->data['Admin']['old_password'];
			$newPassword = $this->data['Admin']['password'];
			$confirmPassword = $this->data['Admin']['password_confirm'];
			
			if ( $oldPassword )
			{
				// Check Password
				$checkPassword = $this->Admin->checkPassword( $oldPassword , $this->Session->read('Auth.Admin.id') );
				
				// If password is valid 
				if ( $checkPassword && $newPassword && $Password == $confirmPassword )
				{
					$this->Admin->updateAll(
						array('Admin.password' => "'".$Password."'"),
						array('Admin.id' => $this->Session->read('Auth.Admin.id') )
					);
					
					$this->Session->setFlash(__('The Admin password has been saved', true));
				}
				else
				{
					if ($checkPassword)
						$this->Session->setFlash(__('Password does not match or not valid.', true));
					else
						$this->Session->setFlash(__('Old Password is not correct.', true));
				}
			}
			$this->redirect(array('action' => 'account'));
		}
	
		$this->set('admin_email',Configure::read('mixpicks.ADMIN_EMAIL'));
		$this->set('title_for_layout','Account');
	}

	//////////////////////////////////////  Configuration  ///////////////////////////////
	// Admin configuration
	function configuration() 
	{
		if (!empty($this->data)) 
		{
			$this->Configuration->create();

			$this->Configuration->updateAll(array('Configuration.value' => "'".$this->data['Admin']['PAYPAL_API_PASSWORD']."'"), array('Configuration.c_key' => 'PAYPAL_API_PASSWORD'));
			$this->Configuration->updateAll(array('Configuration.value' => "'".$this->data['Admin']['PAYPAL_API_USERNAME']."'"), array('Configuration.c_key' => 'PAYPAL_API_USERNAME'));
			$this->Configuration->updateAll(array('Configuration.value' => "'".$this->data['Admin']['PAYPAL_API_SIGNATURE']."'"), array('Configuration.c_key' => 'PAYPAL_API_SIGNATURE'));

			$this->Configuration->updateAll(array('Configuration.value' => "'".$this->data['Admin']['TWITTER_USERNAME']."'"), array('Configuration.c_key' => 'TWITTER_USERNAME'));
			
			$this->Session->setFlash(__('Configuration settings has been saved.', true));
			$this->redirect(array('action' => 'configuration'));
		}
		
		$this->set('api_password',Configure::read('mixpicks.PAYPAL_API_PASSWORD'));
		$this->set('api_username',Configure::read('mixpicks.PAYPAL_API_USERNAME'));
		$this->set('api_signature',Configure::read('mixpicks.PAYPAL_API_SIGNATURE'));
		$this->set('api_endpoint',Configure::read('mixpicks.PAYPAL_API_ENDPOINT'));

		$this->set('merchant_id',Configure::read('mixpicks.GOOGLE_MERCHANT_ID'));
		$this->set('merchant_key',Configure::read('mixpicks.GOOGLE_MERCHANT_KEY'));
		$this->set('server_type',Configure::read('mixpicks.GOOGLE_SERVER_TYPE'));
		
	
		$this->set('title_for_layout','Configuration');
	}

	//////////////////////////////////////  GALLERY  ///////////////////////////////
	// Images Gallery: Originally belongs to Gallery
	function gallery() 
	{
		$this->Album->unBindModel( array('hasOne' => array('Photo'), 'hasMany' => array('Photo') ));
		$albums = $this->Album->getAlbums();
		$this->set('albums', $albums);
		$this->set('title_for_layout','Gallery');
	}

	// Add an album: Originally belongs to Gallery
	function addalbum()
	{
		if (!empty($this->data))
		{
			$this->data['Album']['date_created'] = date('Y-m-d H:i:s');
			
			$this->Album->create();
			if( $this->Album->save($this->data) )
			{
				$this->Session->setFlash(__('Album has been saved.', true));
				$this->redirect(array('action' => 'gallery'));
			}
		}
		$this->set('title_for_layout','Gallery :: Add Album');
	}
	
	// Get Album images: Originally belongs to Gallery
	function photos( $id = null)
	{
		if (!$id)
		$this->redirect(array('action' => 'gallery'));
		
		$this->Album->id = $id;
		$album = $this->Album->read();
		$this->set('album',$album );
		$this->set('title_for_layout','Gallery :: '.$album['Album']['name']);
	}

	// Add images to album: Originally belongs to Gallery
	// Images will be added and uploaded through AJAX request
	function addphotos( $id = null)
	{
	if (!empty($this->data)) {
		$this->autoRender = false;
		
		$image_path = $this->Image->upload_image_and_thumbnail($this->data,'Photo',"imagefile",2048,220,"gallery",true);
		
		if(isset($image_path)) 
		{
			$dataArray['Photo']['name'] = $this->Image->getFileName( $this->data['Photo']['imagefile']['name'] );
			$dataArray['Photo']['filename'] = $image_path;
			$dataArray['Photo']['album_id'] = $this->data['Photo']['album_id'];
			$this->Photo->create();
			$this->Photo->save($dataArray);
		}
			echo '1';
	}
		
		if (!$id)	$this->redirect(array('action' => 'gallery'));
		
		$this->set('uploadify', true);
		$album = $this->Album->read(null,$id);
		$this->set('album', $album);
		$this->set('album_id', $id);
		$this->set('title_for_layout','Gallery :: Upload Photos');
	}

	// Delete images from album: Originally belongs to Gallery
	function delphoto($id = null, $album = null)
	{
		$this->autoRender = false;

		if (!$id)	$this->redirect(array('action' => 'gallery'));
		
		$this->Photo->deletePhoto($id);
		
		if ($this->Photo->delete($id)) {
			$this->Session->setFlash(__('Image deleted', true));
			$this->redirect( array('controller'=>'admin', 'action'=>'photos', $album) );
		}	
	}	

	// Delete album: Originally belongs to Gallery
	function delalbum($id = null)
	{
		$this->autoRender = false;
		if (!$id)	
		$this->redirect(array('action' => 'gallery'));
		
		$this->Album->deleteAlbumPhoto($id);
		
		if ( $this->Album->delete($id) )
		{
			$this->Session->setFlash(__('Album has been deleted', true));
			$this->redirect( array('controller'=>'admin', 'action'=>'gallery') );
		}	
	}	
	//////////////////////////////////////  Articles  /////////////////////////////

	// List articles: Originally belongs to Articles
	function articles()
	{
		$conditions = array();
		$params = array( ''=>'' );
		
		$articles = $this->Article->find('all');
		
		$this->set('articles',$articles);
		$this->set('title_for_layout','Articles');
	}
	
	// Add article: Originally belongs to Articles
	function addarticle()
	{
		if( !empty($this->data) )
		{
			$this->Article->create();
			$image_path = $this->Image->upload_image_and_thumbnail( $this->data,'Article',"articleimage",200,48,"articles",true );

			if( $this->Article->save($this->data) )
			{
				if( isset( $image_path ) )
				{
					$this->Article->saveField('filename', $image_path);
				}

				$this->redirect( array('controller'=>'admin', 'action'=>'articles') );
			}
		}
		$this->set('tiny_mce',true);
		$this->set('title_for_layout','New Article');
	}

	// Edit article: Originally belongs to Articles
	function editarticle( $id=null )
	{
		if (!$id) $this->redirect( array('controller'=>'admin', 'action'=>'articles') );
		
		if( !empty($this->data) )
		{
			$this->Article->id = $id;

			$image_path = $this->Image->upload_image_and_thumbnail( $this->data,'Article',"articleimage",200,48,"articles",true );

			if( $this->Article->save($this->data) )
			{
				if( isset( $image_path ) )
				{
					$this->Article->saveField('filename', $image_path);
				}
				$this->redirect( array('controller'=>'admin', 'action'=>'articles') );
			}
		}
		
		if (empty($this->data)) {
			$this->data = $this->Article->read(null, $id);
		}
		$this->set('article',$this->data);
		$this->set('tiny_mce',true);
		$this->set('title_for_layout','Edit Article');
	}

	// View article: Originally belongs to Articles
	function viewarticle( $id=null )
	{
		if (!$id) $this->redirect( array('controller'=>'admin', 'action'=>'articles') );
		
		$this->data = $this->Article->read(null, $id);
		$this->set('article',$this->data);
	}

	// Delete article: Originally belongs to Articles
	function delarticle( $id = null )
	{
		$this->autoRender = false;
		
		if (!$id) $this->redirect( array('controller'=>'admin', 'action'=>'articles') );
		
		if( $this->Article->delete($id) )
			$this->redirect( array('controller'=>'admin', 'action'=>'articles') );
		else
			$this->redirect( array('controller'=>'admin', 'action'=>'articles') );
	}

	//////////////////////////////////////  Tracks  /////////////////////////////
	// List Tracks: Originally belongs to Tracks
	function tracks()
	{
		$this->Track->unbindModel(array('hasAndBelongsToMany'=>array('Order')),false);
		$tracks = $this->paginate('Track');
		$this->set('tracks', $tracks);

		$this->set('title_for_layout','Tracks');
	}

	// Change track status: Originally belongs to Tracks
	// Disable or enable track
	function trackstatus($id = null)
	{
		$this->autoRender = false;
		
		if (!$id) $this->redirect( array('controller'=>'admin', 'action'=>'tracks') );
		
		$this->Track->changeStatus( $id );
		$this->redirect( array('controller'=>'admin', 'action'=>'tracks') );
	}
	
	// Change track to featured: Originally belongs to Tracks
	function makefeatured($id = null)
	{
		$this->autoRender = false;
		
		if (!$id) $this->redirect( array('controller'=>'admin', 'action'=>'tracks') );
		
		$this->Track->makeFeatured( $id );
		$this->redirect( array('controller'=>'admin', 'action'=>'tracks') );
	}

	//////////////////////////////////////  Members  /////////////////////////////
	// List Members
	function members()
	{
		$this->Member->unbindModel(array('hasOne'=>array('Order'), 'hasMany'=>array('Order') ),false);
		$this->set('members', $this->paginate('Member'));
		$this->set('title_for_layout','Members');
	}

	// View Member
	function memberview($id = null)
	{
		if (!$id) $this->redirect( array('controller'=>'admin', 'action'=>'members') );
		
		$this->Member->unbindModel(array('hasOne'=>array('Order')),false);
		$this->set('member', $this->Member->read(null,$id) );
		$this->set('title_for_layout','Member View');
	}
	
	// Change Member Status 
	function memberstatus($id = null)
	{
		$this->autoRender = false;
		
		if (!$id) $this->redirect( array('controller'=>'admin', 'action'=>'members') );
		
		$this->Member->changeStatus( $id );
		$this->redirect( array('controller'=>'admin', 'action'=>'members') );
	}

	//////////////////////////////////////  Artists  /////////////////////////////
	// List Artists
	function artists()
	{
		$this->Artist->unbindModel(array('hasOne'=>array('Track'), 'hasMany'=>array('Track')),false);
		$this->set('artists', $this->paginate('Artist'));

		$this->set('title_for_layout','Artists');
	}

	// View Artists
	function artistview($id = null)
	{
		if (!$id) $this->redirect( array('controller'=>'admin', 'action'=>'artists') );
		
		$this->Artist->unbindModel(array('hasOne'=>array('Track')),false);
		$this->set('artist', $this->Artist->read(null,$id) );
		$this->set('title_for_layout','Artist View');
	}

	// Change artist status
	function artiststatus($id = null)
	{
		$this->autoRender = false;
		
		if (!$id) $this->redirect( array('controller'=>'admin', 'action'=>'artists') );
		
		$this->Artist->changeStatus( $id );
		$this->redirect( array('controller'=>'admin', 'action'=>'artists') );
	}

	///////////////////////////////////////////// ORDERS ///////////////////////////////////////////////
	// List orders: Originally belongs to Orders
	function orders()
	{
		$this->set('orders', $this->paginate('Order'));
		$this->set('title_for_layout','Orders');
	}
	
	// View order: Originally belongs to Orders
	function orderview( $id = null )
	{
		if (!$id) $this->redirect( array('controller'=>'admin', 'action'=>'orders') );
		
		$this->set('order', $this->Order->read(null,$id) );
		$this->set('title_for_layout','Order View');
	}
	
	function beforeFilter()
	{

		$this->Auth->fields = array('username' => 'username','password' => 'password');
		$this->Auth->loginAction = array('controller' => 'admin', 'action' => 'login');
		$this->Auth->loginRedirect = array('controller' => 'admin', 'action' => 'index');
		$this->Auth->allow('login');
		$this->Auth->userModel = 'Admin';

		parent::beforeFilter();
		$this->set('uploadify', false);
		$this->layout = 'admin';
	}
	
	function logout()
	{
		$this->autoRender = false;
		$this->Session->destroy('Admin');
        $this->Session->setFlash('You\'ve successfully logged out.');
        $this->redirect('login'); 		
	}

	function login()
	{
		$this->layout = 'admin_login';
	}
	
}
?>