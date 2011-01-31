<?php
/////////////////// Site Members Controller /////////////////////////////////

class MembersController extends AppController {
	
	// Models
	var $uses = array('Member','Event','Track','Genre','FriendInvitaion','Photo','Article','Album');
	// Helpers
	var $helpers = array('Html', 'Form','Javascript','Thumbnail','Getid3','Flash');
	// Compnnents
	var $components = array('Auth', 'Tracks','Cart',"Image","Mail");
	
	
	// Home page 
	function index() {

		$this->set( "title_for_layout", "Home" );
		
		
		// Load index page sections
		$this->set( "topTracks",  $this->Track->topSelling( $this ) );
		$this->set( "topArtists",  $this->Tracks->topArtists() );
		$this->set( "recentPhotos",  $this->Photo->getRecentPhoto() );
		$this->set( "recentArticles",  $this->Article->getRecentArticle() );
	}

	
	// Member's Account Page
	function account($id = null) {

		// Get id of signed in user
		$id = $this->Session->read('Auth.Member.id');
		
		// If not signed in and data is posted; redirect to Home page
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(__('Invalid Member', true));
			$this->redirect(array('action' => 'index'));
		}
		
		// Post data not empty
		if (!empty($this->data)) {

			$passwordCheck = true;
			
			// Check user old password
			if ( $this->data['Member']['old_password'] )
			{
				$ckPwd = $this->Member->checkPassword( $this->data, $id);
				$confirmPwd = $this->Member->confirmPassword( $this->data );

				if ( $ckPwd == false || $confirmPwd == false )
				$passwordCheck = false;
			}
			// if old password field is empty clear passwords from data, to avoid updating empty passwords
			else
			{
				unset($this->data['Member']['old_password']);
				unset($this->data['Member']['password']);
				unset($this->data['Member']['password_confirm']);
			}
			
			// If old password is matched			
			if ( $passwordCheck != false )
			{
				// Save members data
				if ($this->Member->save($this->data) ) 
				{
					// Upload member's image
					$image_path = $this->Image->upload_image_and_thumbnail($this->data,'Member',"a_image",573,80,"members",true);
					if(isset($image_path)) 
					{
						// Save member's image in database
						$this->Member->saveField('image',$image_path);
					}
					else 
					{
						$this->Session->setFlash(__('The image for the set could not be saved. Please, try again.', true));
					}	
					
					$this->Session->setFlash(__('The Member has been saved', true));
					$this->redirect(array('action' => 'index'));
				}
				else	
					$this->Session->setFlash(__('The Member could not be saved. Please, try again.', true));
					
			} 
			else // If password entered and is not valid throw an error
			{
				if ( $ckPwd == false )
					$this->Session->setFlash(__('Invalid Password', true));
				elseif( $confirmPwd == false )
					$this->Session->setFlash(__('Password does not match.', true));
			}
		}
	
		// Unbinding models to avoid loading unnecessary data
		$this->Member->unbindModel(array('hasMany'=>array('Order'),'hasOne'=>array('Order')),false);
		// Load members data
		$this->data = $this->Member->read(null, $id);
		$this->set('data', $this->data);
	}
	
	// Before render function	
	function  beforeRender()
	{
		if ( $this->Session->read('cart') )
		{
			$cartitems = $this->Session->read('cart');
			$this->set('cartitems', ' ('.count($cartitems).')' );
		}
	}
	
	// Member's Login 
	function login()
	{
		if ( $this->Session->read('Auth.Member.name') ) 
		$this->redirect(array('controller' => 'members','action' => 'index'));
		
		$this->set( "title_for_layout", "Member's Login" );
		$this->layout = 'members_login';
	}

	// Newly released tracks; Originally belongs to tracks
	function newreleases()
	{
		$this->set( "title_for_layout", "New Releases" );
		// Unbinding models to avoid loading unnecessary data
		$this->Track->unbindModel(array('hasAndBelongsToMany'=>array('Order')),false);
		
		// Tracks with pagination	
		$this->set('tracks', $this->paginate('Track'));
	}

	// All track listing; Originally belongs to tracks
	function tracks()
	{
		$this->set( "title_for_layout", "Tracks" );

		// Unbinding models to avoid loading unnecessary data
		$this->Track->unbindModel(array('hasAndBelongsToMany'=>array('Order')),false);
		// Tracks with pagination	
		$this->set('tracks', $this->paginate('Track'));
	}
	
	// Artist details; Originally belongs to artist
	function browseartist()
	{
		$this->set( "title_for_layout", "Browse Artist" );

		// Unbinding models to avoid loading unnecessary data
		$this->Artist->unbindModel(array('hasOne'=>array('Track'),'hasMany'=>array('Track')),false);
	
		$this->set('artists', $this->paginate('Artist'));
	}
	
	// Listing artist with sorting options; Originally belongs to artist
	function artist( $id = null, $sort = null )
	{
		if (!$id) {
			$this->Session->setFlash(__('Invalid Artist', true));
			$this->redirect(array('action' => 'index'));
		}
	
		//$this->loadModel('Artist');
		$this->Artist->bindModel( array('hasMany'=>array('Track')), false );
	
		$artist = $this->Artist->read(null, $id);
		
		// Load artist page elements
		$this->set('ArtistTopSeller', $this->Track->bestSeller($id) );
		$this->set('specialPicks', $this->Track->specialPicks() );
		
		$this->set( "title_for_layout", $artist['Artist']['name'] );
		$this->set('artist', $artist);
		
		
		if ( $sort == 'date' )
		{
			$tracks =  $this->Track->getByDate($id);
			$this->set('tracks', $tracks);
		}
		elseif ( $sort == 'top' )
		{
			$tracks =  $this->Track->getTop($id);
			$this->set('tracks', $tracks);
		}
		elseif ( $sort == 'fav' )
		{
			$tracks =  $artist['Track'];
			$this->set('tracks', $tracks);
		}
		else
		{
			$tracks =  $artist['Track'];
			$this->set('tracks', $tracks);
		}
	}
	
	// Shopping cart; Originally belongs to cart
	function cart ($s=null)
	{
		$this->set( "title_for_layout", 'Your Cart' );
		
		$this->Track->unbindModel(array('hasAndBelongsToMany'=>array('Order')),false);
		
		$cartTracks = $this->Session->read('cart');
		
		$params  = array( 'conditions' => array('Track.id'=>$cartTracks) );
		$cartTracks = $this->Track->find('all',$params);
		$this->set('cartTracks', $cartTracks);
		
		$referer = '';
		if ( $s ) $referer = $this->referer();
		
		$this->set('referer', $referer);
	}

	// Add to cart; Originally belongs to cart
	function addcart ( $id )
	{
		$this->autoRender = false;
		
		if (!$id) {
			$this->Session->setFlash(__('Invalid Item', true));
			$this->redirect(array('action' => 'index'));
		}

		if ( !$this->Session->read('cart') )
		{
			$this->Session->write('cart',array());
		}

		$cartItems = $this->Session->read('cart');
		if ( count($cartItems) < 1 )
		{
			$this->Session->write('cart',array($id));
		}
		else
		{
			$cartItems = $this->Cart->addItem( $cartItems ,$id );
			$this->Session->write('cart',$cartItems);
		}
        //$this->redirect('cart'); 
		$this->redirect(array('action' => 'cart', 's'));

	}

	// Remove cart: Originally belongs to cart
	function removecart( $id )
	{
		$this->layout = false;
		$this->autoRender = false;
		
		if (!$id) {
			$this->Session->setFlash(__('Invalid Item', true));
			$this->redirect(array('action' => 'index'));
		}

		$cartItems = $this->Session->read('cart');

		if ( count($cartItems) == 1 )
		{
			$this->Session->write('cart',null);
		}
		else
		{
			$cartItems = $this->Cart->removeItem( $cartItems ,$id );
			$this->Session->write('cart',$cartItems);
		}
        $this->redirect('cart'); 
	}
	
	// Member's logout page
	function logout() 
	{
		$this->Session->destroy('Member');
        $this->Session->setFlash('You\'ve successfully logged out.');
        $this->redirect('login'); 
	}

	// Before filter
	function beforeFilter()
	{
		parent::beforeFilter();
		
		$this->Auth->fields = array('username' => 'email','password' => 'password');
		$this->Auth->loginAction = array('controller' => 'members', 'action' => 'login');
		$this->Auth->loginRedirect = array('controller' => 'members', 'action' => 'index');
		$this->Auth->allow('login');
		$this->Auth->userModel = 'Member';
		$this->layout = 'default';
		
		// Load elements
		$this->set('galleryArtists', $this->Artist->galleryArtists() );
		$this->set('topGrossingArtists', $this->Artist->topGrossingArtists() );
		$this->set('topMonthlyArtists', $this->Artist->topMonthlyArtists() );
		$this->set('featuredTracks', $this->Track->featured() );
		$this->set('upcomingEvents', $this->Event->upcomingEvents());
	}
	
	// Events: Originally belongs to Events
	function event($id = null) {
		if (!$id) {
			$this->flash(__('Invalid Event', true), array('action'=>'calendar'));
		}

		$this->set('event', $this->Event->getEvent($id);
	}
	
	// Events: Originally belongs to Events
	function calendar( $year=null, $month=null, $all=null, $user=null) // Parameters are for sorting
	{
		$year = ( $year ) ? $year : date('Y');
		$month = ( $month ) ? ( $month < 10) ? '0'.$month : $month : date('m');

		$nextWeek = time() + 518400; // 6 * 24 * 60 * 60

		if ($all)
			$startDate = date($year.'-'.$month.'-01');
		else
			$startDate = date($year.'-'.$month.'-d');

		if ($all)
			$endDate = date($year.'-'.$month.'-31', $nextWeek);
		else
			$endDate = date($year.'-'.$month.'-d', $nextWeek);
		
		$conditions = array('Event.event_date BETWEEN ? AND ?'=>array($startDate , $endDate) ) ;
		$fields = array("Event.*, DATE_FORMAT(event_date,'%e') as e_day, DATE_FORMAT(event_to,'%h:%i %p') as e_time, DATE_FORMAT(event_date,'%W, %M %e') as e_daymonth");
		
		$params = array( 'conditions'=>$conditions, 'fields'=>$fields );
		
		$date = $this->Event->find('all',$params);

		$this->set('data', $date);
		$this->set('year', $year);
		$this->set('month', $month);
	}


	// Tracks genre: Originally belongs to Tracks
	function genre( $genre = null)
	{
		$genres = $this->Genre->load();
		$tracks = $this->Track->byGenre($genre);
		
		$this->set('genres', $genres);
		$this->set('tracks', $tracks);
	}

	// Article View: Originally belongs to Article
	function viewarticle( $id=null )
	{
		if (!$id) $this->redirect( array('controller'=>'admin', 'action'=>'articles') );
		
		$this->data = $this->Article->read(null, $id);
		$this->set('article',$this->data);
	}
	
	function viewalbum( $album = null,  $pic = null )
	{
		$this->Album->id = $album;
		$album = $this->Album->read();
		$this->set('album',$album );
		$this->set('picture',$pic );
		
		$this->set('title_for_layout','Gallery :: '.$album['Album']['name']);
		
		$this->set('albums', $this->Album->getOtherAlbum() );
	}
	
	// Invite a friend: Originally belongs to Invitations
	function invitefriend()
	{
		$this->autoRender = false;
		
		if (!empty($this->data))
		{
			$sentEmails = $this->Mail->inviteFriends ( $this->data,$this );
			
			$inviteData['FriendInvitaion']['user_id'] = $this->Session->read("Auth.Member.id");
			$inviteData['FriendInvitaion']['emails'] = implode(",", $sentEmails['emails']);
			$inviteData['FriendInvitaion']['message'] = $sentEmails['message'];
			$inviteData['FriendInvitaion']['code'] = $sentEmails['code'];
			
			$this->FriendInvitaion->create();
			$this->FriendInvitaion->save($inviteData);
		}
	}
	
	// Featured Tracks: Originally belongs to Trakcs
	function featured()
	{
		$this->set( "title_for_layout", "Featured 25 Mixes" );
		$this->set('featuredMixes', $this->Track->featured(25) );
	}
	
}
?>