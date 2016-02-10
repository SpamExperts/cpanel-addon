<?php
class SpamFilter_Forms_Diagnostics extends Zend_Form
{
    protected $_paneltype;
    public function __construct($options = null)
    {
		$this->addElementPrefixPath('SpamFilter', 'SpamFilter/');
		parent::__construct($options);
		$this->setMethod('post')
	      	     ->setName('diagnosticsForm');

        $translate = Zend_Registry::get('translator');

		$submit = new Zend_Form_Element_Submit('diagnostics');
		$submit->setLabel($translate->_('Run diagnostics'));
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