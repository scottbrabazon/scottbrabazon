<?php
    if (!$CurrentUser->has_priv('perch_events.categories.manage')) {
         PerchUtil::redirect($API->app_path());
    }
    
    $Categories = new PerchEvents_Categories($API);

    $Form = $API->get('Form');
	
    $message = false;
    
    
    if (isset($_GET['id']) && $_GET['id']!='') {
        $categoryID = (int) $_GET['id'];    
        $Category = $Categories->find($categoryID);
        $details = $Category->to_array();
    }else{
        $categoryID = false;    
        $Category   = false;
        $details    = array();
    }
       
    $Template   = $API->get('Template');
    $Template->set('events/category.html', 'events');

    $Form->handle_empty_block_generation($Template);
    
    $tags = $Template->find_all_tags_and_repeaters();


    $Form->require_field('categoryTitle', 'Required');
    $Form->set_required_fields_from_template($Template, $details);

    if ($Form->submitted()) {
		$postvars = array('categoryTitle');
		
    	$data = $Form->receive($postvars);

        $prev = false;

        if (isset($details['categoryDynamicFields'])) {
            $prev = PerchUtil::json_safe_decode($details['categoryDynamicFields'], true);
        }
        
        $dynamic_fields = $Form->receive_from_template_fields($Template, $prev, $Categories, $Category);
        $data['categoryDynamicFields'] = PerchUtil::json_safe_encode($dynamic_fields);
   
        if (!is_object($Category)) {
            $data['categorySlug'] = PerchUtil::urlify($data['categoryTitle']);
            $Category = $Categories->create($data);
            PerchUtil::redirect($API->app_path() .'/categories/edit/?id='.$Category->id().'&created=1');
        }

        $Category->update($data);
    	
        if (is_object($Category)) {
            $message = $HTML->success_message('Your category has been successfully edited. Return to %scategory listing%s', '<a href="'.$API->app_path() .'/categories">', '</a>');
        }else{
            $message = $HTML->failure_message('Sorry, that category could not be edited.');
        }
        

        // clear the caches
        PerchEvents_Cache::expire_all();

        $details = $Category->to_array();
    }

    if (isset($_GET['created']) && !$message) {
        $message = $HTML->success_message('Your category has been successfully created. Return to %scategory listing%s', '<a href="'.$API->app_path() .'/categories">', '</a>');
    }
