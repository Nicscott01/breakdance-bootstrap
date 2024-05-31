<?php
/**
 *  Breakdance form handler for Fluent CRM
 * 
 * 
 */

namespace BricBreakdance;
use function Breakdance\Elements\control;

class FluentCrmFormHandler extends \Breakdance\Forms\Actions\Action {

    public $variable_items;
    public $download_id;
    public $user_email;


    /**
     * @return string
     */
    public static function name() {
        return 'Fluent CRM';
    }


    public static function slug()
    {
        return 'fluent_crm';
    }
    

    /**
    * Log the form submission to a file
    *
    * @param array $form
    * @param array $settings
    * @param array $extra
    * @return array success or error message
    */
    public function run($form, $settings, $extra)
    {
        try {
           
            //Add or update email in Fluent, and tag them with the form they filled out

        } catch(\Exception $e) {
            return ['type' => 'error', 'message' => $e->getMessage()];
        }

        return ['type' => 'success', 'message' => 'Please check your email!'];
    }



    

}