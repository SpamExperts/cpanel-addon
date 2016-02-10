<?php
class SpamFilter_Forms_Bulkprotect extends Zend_Form
{
    public function __construct($options = null)
    {
		$this->addElementPrefixPath('SpamFilter', 'SpamFilter/');
		parent::__construct($options);
				
      	$this->setMethod('post')->setName('bulkProtect');

        $translate = Zend_Registry::get('translator');
/*
		$background = new Zend_Form_Element_Checkbox('run_background');
		$background->setLabel('Run in background. ')
  	                        ->setAttrib('title', "Run the bulkprotect in the background");
		$this->addElement( $background );			
*/
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($translate->_('Execute bulkprotect'));
		$submit->setAttrib('class', 'btn btn-primary');		
		$this->addElement( $submit );
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