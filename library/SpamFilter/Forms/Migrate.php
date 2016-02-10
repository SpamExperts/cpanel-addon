<?php
class SpamFilter_Forms_Migrate extends Zend_Form
{
    protected $_paneltype;
    public function __construct($options = null)
    {
		$this->addElementPrefixPath('SpamFilter', 'SpamFilter/');
		parent::__construct($options);
		$this->setMethod('post')
	      	     ->setName('migrateForm');

		$config    = Zend_Registry::get('general_config');
        $translate = Zend_Registry::get('translator');

		$current_user = new Zend_Form_Element_Text('current_user');
		$current_user->setLabel($translate->_('Current username'))
		          ->setRequired( True )
		          ->addValidator('NotEmpty')
       		      ->setValue( $config->apiuser )
				  ->addValidator(new Zend_Validate_Identical($config->apiuser))
				  ->setAttrib('readonly', true )
				  ->setAttrib('class', 'span3')
				  ->setAttrib('title', $translate->_('Current username'))
		          ->setAttrib('data-content', $translate->_("This is the current username that is being replaced."));
		$this->addElement( $current_user );
		## new user

		$new_user = new Zend_Form_Element_Text('new_user');
		$new_user->setLabel($translate->_('New username'))
		          ->setRequired(true)
		          ->addValidator('NotEmpty')
				  ->addValidator(new SpamFilter_Validate_NotIdentical($config->apiuser))
				  ->setAttrib('title', $translate->_('New username'))
		          ->setAttrib('data-content', $translate->_("This is the name of the user that is being used to communicate with the spamfilter."))
				  ->setAttrib('class', 'span3')
				  ->setErrorMessages( array($translate->_("This should not be empty or identical to the current user.")) );
		$this->addElement( $new_user );

		$new_pass = new Zend_Form_Element_Password('new_password');
		$new_pass->setLabel($translate->_('New password'))
		          ->setRequired(true)
		          ->addValidator('NotEmpty')
				  ->setAttrib('autocomplete', 'off')
				  ->setAttrib('class', 'span3')
				  ->setAttrib('title', $translate->_('New password'))
		          ->setAttrib('data-content', $translate->_("This is the password from the user that is being used to communicate with the spamfilter."));
		$this->addElement( $new_pass );

		if( (!empty($_POST)) )
		{
			$new_pass->addValidator( new SpamFilter_Validate_ApiCredentials( $config->apihost, $_POST['new_user'], $_POST['new_password'], $config->ssl_enabled) );
		}

		$confirmation = new Zend_Form_Element_Checkbox('confirmation');
		$confirmation->setLabel($translate->_('I am sure I want to migrate all protected domains on this server to this new user.'))
						  ->setChecked( False )
						  ->setRequired( True )
						  ->addValidator(new Zend_Validate_InArray(array(1)), false)
						  ->setAttrib('title', $translate->_('Confirmation'))
  	                      ->setAttrib('data-content', $translate->_("Please confirm whether you want to do this."))
						  ->setErrorMessages( array($translate->_("You have to confirm that you want to execute this action")) );
		$this->addElement( $confirmation );


		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($translate->_('Migrate'));
		$submit->setAttrib('class', 'btn btn-primary');
		$this->addElement( $submit );

		$this->setAttrib('enctype', 'multipart/form-data');
	}

    public function isValid($data)
    {
        $valid = parent::isValid($data);

        foreach ($this->getElements() as $element) {
            if ($element->hasErrors())
			{
                $oldClass = $element->getAttrib('class');
                if (!empty($oldClass)) {
                    $element->setAttrib('class', $oldClass . ' inputerror');
                } else {
                    $element->setAttrib('class', 'inputerror');
                }
            }
        }
        return $valid;
    }
}
