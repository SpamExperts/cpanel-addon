<?php
class SpamFilter_Forms_BrandingConfig extends Twitter_Form
{
    public function __construct($brandUsed = null,$options = null)
    {
		$this->addElementPrefixPath('SpamFilter', 'SpamFilter' . DS);
		parent::__construct($options);
		$this->setMethod('post')
			 ->setName('brandingForm');

        $translate = Zend_Registry::get('translator');

        if (empty($brandUsed)) {
            $brand     = new SpamFilter_Brand();
            $brandUsed = $brand->getBrandUsed();
        }

		// Brandname
		$brandname = new Zend_Form_Element_Text('brandname');
		$brandname->setLabel($translate->_('Brandname'))
								->setRequired(true)
								->addValidator( new Zend_Validate_Alnum( array('allowWhiteSpace' => true ) ) )//<-- Only a-zA-Z0-9 to be safe. Spaces are allowed.
								->addValidator('NotEmpty')
								->setValue( $brandUsed )
								->setAttrib('class', 'span3')
								->setAttrib('title', $translate->_('Brandname'))
								->setAttrib('data-content', $translate->_("This is the brandname used to reference to the addon in the customer part of the controlpanel."));
		$this->addElement( $brandname );

		if (in_array(strtoupper(SpamFilter_Core::getPanelType()), array('CPANEL', 'PLESK'))) {
				// Show icon (if panel is supported)
                                $destination =  TMP_PATH;
				$brandicon = new Zend_Form_Element_File('brandicon');
				$brandicon->setLabel($translate->_('Brandicon'))
										->setRequired(false)
										->setDestination($destination)
										->addValidator('Count', false, 1)
										->addValidator('Size', false, 102400)
										->addValidator('Extension', false, 'png')
										#->addValidator('IsImage', false, 'png')
										->addValidator('ImageSize', false, array('minwidth' => 32, 'minheight' => 32, 'maxwidth' => 48, 'maxheight' => 48))//48x48for paper_lantern, if 48x48 it will resize for other themes
										->setAttrib('title', $translate->_('Brandicon'))
										->setAttrib('data-content', $translate->_("This is the brandicon (48x48px, non transparent PNG) used in the customer part of the controlpanel."));
				$this->addElement( $brandicon );
		} else {
            $brandicon = new Zend_Form_Element_Hidden('brandicon');
            $brandicon->setAttrib('value', '');
            $this->addElement( $brandicon );
        }

		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($translate->_('Save Branding Settings'));
		$submit->setAttrib('class', 'btn btn-primary');
		$this->addElement( $submit );

		$this->setAttrib('enctype', 'multipart/form-data');
        $this->setAttrib('horizontal', 1);
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
