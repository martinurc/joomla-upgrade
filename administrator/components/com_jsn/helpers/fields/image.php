<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


global $_FIELDTYPES;
$_FIELDTYPES['image']='COM_JSN_FIELDTYPE_IMAGE';

class JsnImageFieldHelper
{
	public static function create($alias)
	{
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users ADD ".$db->quoteName($alias)." VARCHAR(255)";
		$db->setQuery($query);
		$db->query();
	}
	
	public static function delete($alias)
	{
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users DROP COLUMN ".$db->quoteName($alias);
		$db->setQuery($query);
		$db->query();
	}
	
	public static function getXml($item)
	{
		require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
		$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
		if(JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn' && $item->params->get('titleprofile','')!='') $item->title=$item->params->get('titleprofile','');
		$defaultvalue=($item->params->get('image_defaultvalue','')!='' ? 'default="'.JsnHelper::xmlentities($item->params->get('image_defaultvalue','')).'"' : '');//(isset($item->params['image_defaultvalue']) && $item->params['image_defaultvalue']!='' ? 'default="'.$item->params['image_defaultvalue'].'"' : '');
		
		if($item->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
		elseif($item->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('view')!='registration' && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
		else $readonly='';

		$xml='
			<field
				name="'.$item->alias.'"
				type="imagefull"
				id="'.$item->alias.'"
				imageclass="'.$item->alias.' '.$item->params->get('image_class','').'"
				class="'.$item->params->get('field_cssclass','').'"
				description="'.JsnHelper::xmlentities(($item->description)).'"
				accept="image/*"
				label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
				alt="'.$item->params->get('image_alt','').'"
				'.$defaultvalue.'
				'.$readonly.'
				width="'.$item->params->get('image_width','500').'"
				height="'.$item->params->get('image_height','500').'"
				width_thumb="'.$item->params->get('image_thumbwidth','100').'"
				height_thumb="'.$item->params->get('image_thumbheight','100').'"
				cropwebcam="'.$item->params->get('image_cropwebcam','0').'"
				required="'.($item->required && JFactory::getApplication()->input->get('jform',null,'array')==null ? ($item->required==2 ? 'admin' : 'frontend' ) : 'false' ).'"
				requiredfile="'.($item->required ? ($item->required==2 ? 'admin' : 'frontend' ) : 'false' ).'"
				validate="image"
			/>
		';
		return $xml;
	}
	
	public static function loadData($field, $user, &$data)
	{
		$alias=$field->alias;
		$alias_clean=$field->alias.'_clean';
		$alias_mini=$field->alias.'_mini';

		$jsn_config = JComponentHelper::getParams('com_jsn');
		if($alias=='avatar' && $jsn_config->get('avatar',1) == 2 && isset($data->email)) // Gravatar
		{
			$email = $data->email;
			if($field->params->get('image_defaultvalue','')!='')
				$default = '&d='.urlencode(JURI::root().$field->params->get('image_defaultvalue',''));
			else
				$default = '';
			$size = $field->params->get('image_height',500);
			$size_mini = $field->params->get('image_thumbwidth',100);

			$data->$alias = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $email ) ) ) . "?s=" . $size . $default;
			$data->$alias_mini = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $email ) ) ) . "?s=" . $size_mini . $default;
			return;
		}

		if(isset($user->$alias) && $user->$alias!='' && file_exists(JPATH_SITE.'/'.$user->$alias))
		{
			$data->$alias=$user->$alias;
			$data->$alias_clean=$data->$alias;
			$data->$alias_mini=preg_replace('~_(?!.*_)~', 'mini_', $data->$alias);
			if(!file_exists(JPATH_SITE.'/'.$data->$alias_mini)) $data->$alias_mini=$user->$alias;
		}
		/*elseif($field->params->get('image_defaultvalue','')!='')
		{
			$data->$alias=$field->params->get('image_defaultvalue','');
			$data->$alias_mini=$data->$alias;
			$data->$alias_clean='';
		}
		elseif($alias == 'avatar')
		{
			$data->$alias='components/com_jsn/assets/img/default.jpg';
			$data->$alias_mini='components/com_jsn/assets/img/default.jpg';
			$data->$alias_clean='';
		}*/
	}
	
	public static function storeData($field, $data, &$storeData)
	{
		$jsn_config = JComponentHelper::getParams('com_jsn');
		if($field->alias == 'avatar' && $jsn_config->get('avatar',1) == 2) // Gravatar
		{
			return;
		}

		//if($field->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) return;
		//if($field->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('task')=='profile.save' && JFactory::getApplication()->isSite()) return;
		$upload_path=$field->params->get('image_path','images/profiler/');

		// Set Upload Dir
		$upload_dir=JPATH_SITE.'/'.$upload_path;
		if(!file_exists($upload_dir)) 
		{ 
			mkdir($upload_dir); 
		}

		// Get Alias
		$alias=$field->alias;
		if(isset($data[$alias])) $storeData[$alias]=$data[$alias];

		// Delete Image
		$jform=JFactory::getApplication()->input->post->getArray();
		if(isset($storeData[$alias]) && $storeData[$alias]=='')
		{
			// Delete old file
			foreach (glob($upload_dir.$alias.$data['id'].'*') as $deletefile)
			{
				unlink($deletefile);
			}
			
			$storeData[$alias]='';
			return;
		}

		/* ------ METHOD INPUT ------ */
		$jform=JFactory::getApplication()->input->files->get('jform',null,'raw');
		if(isset($jform['upload_'.$alias])) $jform_file=$jform['upload_'.$alias];
		if(isset($jform_file['name']) && strlen($jform_file['name'])>4)
			$fileArray=array(
				'name' => $jform_file['name'],
				'type' => $jform_file['type'],
				'tmp_name' => $jform_file['tmp_name'],
				'error' => $jform_file['error'],
				'size' => $jform_file['size'],
			);
		else
			$fileArray=array();

		if(file_exists(JPATH_ADMINISTRATOR.'/components/com_k2/lib/class.upload.php')) require_once(JPATH_ADMINISTRATOR.'/components/com_k2/lib/class.upload.php');
		else require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/assets/class.upload.php');
		$foo = new Upload($fileArray);
		$session = JFactory::getSession();
		if ($foo->uploaded)
		{
			// Delete old file
			foreach (glob($upload_dir.$alias.$data['id'].'*') as $deletefile)
			{
				unlink($deletefile);
			}
			
			$md5=md5(time().rand());
			// Store & Resize Image Thumbs
			$filename=$alias.$data['id'].'mini_'.$md5;
			$foo->file_new_name_body = $filename;
			$foo->image_resize = true;
			$foo->image_ratio_crop = true;
			$foo->image_convert = 'png';
			if($field->params->get('image_thumbwidth',100)>0) $foo->image_x = $field->params->get('image_thumbwidth',100);//$field->params['image_thumbwidth'];
			if($field->params->get('image_thumbheight',100)>0) $foo->image_y = $field->params->get('image_thumbheight',100);//$field->params['image_thumbheight'];
			//die($foo->image_x);
			$foo->Process($upload_dir);
			// Store & Resize Image
			$filename=$alias.$data['id'].'_'.$md5;
			$foo->file_new_name_body = $filename;
			$foo->image_resize = true;
			$foo->image_ratio_crop = true;
			$foo->image_convert = 'png';
			if($field->params->get('image_width',500)>0) $foo->image_x = $field->params->get('image_width',500);//$field->params['image_width'];
			if($field->params->get('image_height',500)>0) $foo->image_y = $field->params->get('image_height',500);//$field->params['image_height'];
			$foo->Process($upload_dir);
			if ($foo->processed)
			{
				$storeData[$alias]=$upload_path.$foo->file_dst_name;
				$foo->Clean();
			}
		}
		/* ------ METHOD CROP & WEBCAM ------ */
		if($session->get('_tmp_img_'.$alias,'')!=''){
			$md5=md5(time().rand());
			$file = $session->get('_tmp_img_'.$alias,'');
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			$filename=$alias.$data['id'].'_'.$md5.'.'.$ext;
			$filename_mini=$alias.$data['id'].'mini_'.$md5.'.'.$ext;
			$path = JPATH_SITE.'/images/_tmp/';
			if(file_exists($path . str_replace('.','-big.',$file)) && file_exists($path . str_replace('.','-big.',$file))){
				foreach (glob($upload_dir.$alias.$data['id'].'*') as $deletefile)
				{
					unlink($deletefile);
				}
				rename($path . str_replace('.','-big.',$file),$upload_dir.$filename);
				rename($path . str_replace('.','-mini.',$file),$upload_dir.$filename_mini);
				unlink($path . $file);
				$storeData[$alias]=$upload_path.$filename;
			}
			/* Clean Session Var */
			$session->set('_tmp_img_'.$alias,'');
		}
		/* ------ METHOD CROP ------ */
		$images = Slim::getImages('imgupload_'.$alias);
		if(count($images)){

			foreach ($images as $image) {
				switch ($image['input']['type']) {
					case 'image/gif':
					    $extension = '.gif';
					    break;
					case 'image/jpeg':
					    $extension = '.jpg';
					    break;
					case 'image/png':        
					    $extension = '.png';
					    break;
					default:
					    $extension = '.jpg';
					    break;
				}
				// Clean Old Images
				foreach (glob($upload_dir.$alias.$data['id'].'*') as $deletefile)
				{
					unlink($deletefile);
				}

				// Copy uploaded images
				$md5=md5(time().rand());
				$filename=$alias.$data['id'].'_'.$md5.$extension;
				$filename_mini=$alias.$data['id'].'mini_'.$md5;
			    $file = Slim::saveFile($image['output']['data'], $filename, $upload_dir,false);
			    $file = Slim::saveFile($image['output']['data'], $filename_mini, $upload_dir,false);
			    $storeData[$alias]=$upload_path.$filename;
			    
			    // Resize Thumbs
			    $handle = new upload($upload_dir.$filename_mini);
			    $handle->file_new_name_body=$filename_mini;
			    if($field->params->get('image_thumbwidth',100)>0) $handle->image_x = $field->params->get('image_thumbwidth',100);
			    if($field->params->get('image_thumbheight',100)>0) $handle->image_y = $field->params->get('image_thumbheight',100);
			    $handle->image_resize = true;
			    $handle->image_ratio_crop = true;
			    $handle->process($upload_dir);
			    $handle->clean();
			    
			}
		}

		/* Temp folder path to clean */
		$path = JPATH_SITE.'/images/_tmp/';

		/* Clean Tmp folder from not saved images */
		if($session->get('_tmp_rand_'.$alias,'')!='')
		{
			
			$rand=$session->get('_tmp_rand_'.$alias,'')!='';

			$filename_prefix=substr(md5($_SERVER['REMOTE_ADDR'].$alias.$rand),0,10);
			
			if (file_exists($path))
			{
				foreach (glob($path.$filename_prefix.'*') as $deletefile)
				{
					unlink($deletefile);
				}
			}
		}
	}

	public static function getSearchInput($field)
	{
		$return='<fieldset class="checkboxes" id="jform_'.str_replace('-','_',$field->alias).'">
					<label class="checkbox inline"><input type="checkbox" name="'.$field->alias.'" value="1"'.(JFactory::getApplication()->input->get($field->alias,'','raw')!='' ? "checked=checked" : '').' /><b>'.JText::_('JYES').'</b></label>
				</fieldset>';
		return $return;
	}
	
	public static function getSearchQuery($field, &$query)
	{
		$db=JFactory::getDbo();
		$query->where('b.'.$db->quoteName($field->alias).' LIKE '.$db->quote('_%'));
	}
	
	public static function image($field,$user)
	{
		$value=$field->__get('value');
		if (empty($value) && $field->name!='jform[avatar]' && $field->name!='avatar')
		{
			return JHtml::_('users.value', $value);
		}
		else
		{
			return $field->getImage($user);
		}
		
	}

	public static function deleteUser($field,$user){
		$upload_path=$field->params->get('image_path','images/profiler/');

		// Set Upload Dir
		$upload_dir=JPATH_SITE.'/'.$upload_path;

		$userId = \Joomla\Utilities\ArrayHelper::getValue($user, 'id', 0, 'int');
		
		if($userId > 0) foreach (glob($upload_dir.$field->alias.$userId.'*') as $deletefile)
		{
				unlink($deletefile);
		}
	}
	
	public static function operations()
	{
		JFactory::getConfig()->set('gzip',false);
		$db=JFactory::getDbo();
		$query=$db->getQuery(true);
		$query->select('params')->from('#__jsn_fields')->where('alias='.$db->quote(JFactory::getApplication()->input->get('field')));
		$params = new JRegistry;
		$db->setQuery($query);
		$params->loadString($db->loadResult());
		$name=JFactory::getApplication()->input->get('field');

		$session = JFactory::getSession();
		

		require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/assets/ImgPicker.php');

		if($session->get('_tmp_rand_'.$name,'')=='') $session->set('_tmp_rand_'.$name,md5(time().rand()));

		$rand=$session->get('_tmp_rand_'.$name,'');

		$filename=substr(md5($_SERVER['REMOTE_ADDR'].$name.$rand),0,10);

		$upload_tmp  = JPATH_SITE.'/images/_tmp/';
		if (!file_exists($upload_tmp)) {
		    mkdir($upload_tmp);
		}

		$options = array(

			// Upload directory path
			'upload_dir' => $upload_tmp,

			// Upload directory url:
			//'upload_url' => 'http://localhost/imgPicker/files/',
			'upload_url' => JURI::root(true) . '/images/_tmp/',

			// Accepted file types:
			'accept_file_types' => 'png|jpg|jpeg|gif',
			
			// Directory mode:
			'mkdir_mode' => 0777,
			
			// File size restrictions (in bytes):
			'max_file_size' => null,
		    'min_file_size' => 1,
		    
		    // Image resolution restrictions (in px):
		    'max_width'  => null,
		    'max_height' => null,
		    'min_width'  => 1,
		    'min_height' => 1,

		    // Image versions:
		    'versions' => array(
		    	// This will create 2 image versions: the original one and a 200x200 one
		    	'mini' => array(
		    		//'upload_dir' => '',
		    		//'upload_url' => '',
		    		// Create square image
		    		//'crop' => true,
		    		'max_width' => $params->get('image_thumbwidth',100), 
		    		'max_height' => $params->get('image_thumbheight',100)
		    	),
		    	'big' => array(
		    		//'upload_dir' => '',
		    		//'upload_url' => '',
		    		// Create square imag
		    		//'crop' => true,
		    		'max_width' => $params->get('image_width',500), 
		    		'max_height' => $params->get('image_height',500)
		    	),
		    ),

		    /**
			 * 	Load callback
			 *
			 *  @param 	ImgPicker 		$instance
			 *  @return string|array
			 */
		    'load' => function($instance) {
		    	//return 'avatar.jpg';
		    },

		    /**
			 * 	Delete callback
			 *
			 *  @param  string 		    $filename
			 *  @param 	ImgPicker 		$instance
			 *  @return boolean
			 */
		    'delete' => function($filename, $instance) {
		    	return true;
		    },
			
			/**
			 * 	Upload start callback
			 *
			 *  @param 	stdClass 		$image
			 *  @param 	ImgPicker 		$instance
			 *  @return void
			 */
			'upload_start' => function($image, $instance) {
				$session = JFactory::getSession();
				$rand=$session->get('_tmp_rand_'.JFactory::getApplication()->input->get('field'));
				$filename=substr(md5($_SERVER['REMOTE_ADDR'].JFactory::getApplication()->input->get('field').$rand),0,10);
				$image->name = $filename . '.' . $image->type;		
			},
			
			/**
			 * 	Upload complete callback
			 *
			 *  @param 	stdClass 		$image
			 *  @param 	ImgPicker 		$instance
			 *  @return void
			 */
			'upload_complete' => function($image, $instance) {
			},

			/**
			 * 	Crop start callback
			 *
			 *  @param 	stdClass 		$image
			 *  @param 	ImgPicker 		$instance
			 *  @return void
			 */
			'crop_start' => function($image, $instance) {
				$session = JFactory::getSession();
				$rand=$session->get('_tmp_rand_'.JFactory::getApplication()->input->get('field'));
				$filename=substr(md5($_SERVER['REMOTE_ADDR'].JFactory::getApplication()->input->get('field').$rand),0,10);
				$image->name = $filename . '.' . $image->type;
				$session->set('_tmp_img_'.JFactory::getApplication()->input->get('field'),$filename . '.' . $image->type);
			},

			/**
			 * 	Crop complete callback
			 *
			 *  @param 	stdClass 		$image
			 *  @param 	ImgPicker 		$instance
			 *  @return void
			 */
			'crop_complete' => function($image, $instance) {
				
			}
		);

		// Create new ImgPicker instance
		new ImgPicker($options);

	}

}


abstract class SlimStatus {
    const FAILURE = 'failure';
    const SUCCESS = 'success';
}

class Slim {

    public static function getImages($inputName = 'slim') {

        $values = Slim::getPostData($inputName);

        // test for errors
        if ($values === false) {
            return false;
        }

        // determine if contains multiple input values, if is singular, put in array
        $data = array();
        if (!is_array($values)) {
            $values = array($values);
        }

        // handle all posted fields
        foreach ($values as $value) {
            $inputValue = Slim::parseInput($value);
            if ($inputValue) {
                array_push($data, $inputValue);
            }
        }

        // return the data collected from the fields
        return $data;

    }

    // $value should be in JSON format
    private static function parseInput($value) {

        // if no json received, exit, don't handle empty input values.
        if (empty($value)) {return null;}

        // The data is posted as a JSON String so to be used it needs to be deserialized first
        $data = json_decode($value);

        // shortcut
        $input = null;
        $actions = null;
        $output = null;
        $meta = null;

        if (isset ($data->input)) {
            $inputData = isset($data->input->image) ? Slim::getBase64Data($data->input->image) : null;
            $input = array(
                'data' => $inputData,
                'name' => $data->input->name,
                'type' => $data->input->type,
                'size' => $data->input->size,
                'width' => $data->input->width,
                'height' => $data->input->height,
            );
        }

        if (isset($data->output)) {
            $outputData = isset($data->output->image) ? Slim::getBase64Data($data->output->image) : null;
            $output = array(
                'data' => $outputData,
                'width' => $data->output->width,
                'height' => $data->output->height
            );
        }

        if (isset($data->actions)) {
            $actions = array(
                'crop' => $data->actions->crop ? array(
                    'x' => $data->actions->crop->x,
                    'y' => $data->actions->crop->y,
                    'width' => $data->actions->crop->width,
                    'height' => $data->actions->crop->height,
                    'type' => $data->actions->crop->type
                ) : null,
                'size' => $data->actions->size ? array(
                    'width' => $data->actions->size->width,
                    'height' => $data->actions->size->height
                ) : null
            );
        }

        if (isset($data->meta)) {
            $meta = $data->meta;
        }

        // We've sanitized the base64data and will now return the clean file object
        return array(
            'input' => $input,
            'output' => $output,
            'actions' => $actions,
            'meta' => $meta
        );
    }

    // $path should have trailing slash
    public static function saveFile($data, $name, $path = 'tmp/', $uid = true) {

        // Add trailing slash if omitted
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }
        
        // Test if directory already exists
        if(!is_dir($path)){
            mkdir($path, 0755, true);
        }

        // Let's put a unique id in front of the filename so we don't accidentally overwrite older files
        if ($uid) {
            $name = uniqid() . '_' . $name;
        }

        // Add name to path, we need the full path including the name to save the file
        $path = $path . $name;

        // store the file
        Slim::save($data, $path);

        // return the files new name and location
        return array(
            'name' => $name,
            'path' => $path
        );
    }

    public static function outputJSON($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    /**
     * Gets the posted data from the POST or FILES object. If was using Slim to upload it will be in POST (as posted with hidden field) if not enhanced with Slim it'll be in FILES.
     * @param $inputName
     * @return array|bool
     */
    private static function getPostData($inputName) {

        $values = array();

        if (isset($_POST[$inputName])) {
            $values = $_POST[$inputName];
        }
        else if (isset($_FILES[$inputName])) {
            // Slim was not used to upload this file
            return false;
        }

        return $values;
    }

    /**
     * Saves the data to a given location
     * @param $data
     * @param $path
     */
    private static function save($data, $path) {
        file_put_contents($path, $data);
    }

    /**
     * Strips the "data:image..." part of the base64 data string so PHP can save the string as a file
     * @param $data
     * @return string
     */
    private static function getBase64Data($data) {
        return base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data));
    }

}
