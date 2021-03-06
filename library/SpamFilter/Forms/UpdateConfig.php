<?php
class SpamFilter_Forms_UpdateConfig extends Twitter_Form
{
    protected $_paneltype;
    public function __construct($options = null)
    {
        $this->addElementPrefixPath('SpamFilter', 'SpamFilter/');
        parent::__construct($options);

        $config    = Zend_Registry::get('general_config');
        $translate = Zend_Registry::get('translator');

        $this->setMethod('post')->setName('forceUpdate');

        $restrictToFrozen = SpamFilter_Core::isRestrictedToFrozenTier();
        $tier_options = array();
        if ($restrictToFrozen) {
            $tier_options['frozen'] = sprintf(
                $translate->_("Frozen builds (legacy support for PHP < %s)"),
                SpamFilter_Core::PHP5_RECOMMENDED_VERSION
            );
        } else {
            $tier_options['stable']  = $translate->_("Stable (preferred, released builds)");
            $tier_options['testing'] = $translate->_("Testing (optional, master builds)");
        }

        $type = new Zend_Form_Element_Select('update_type');
        $type->setLabel($translate->_("Tier of addon to install"))
                ->setRequired( true )
                ->setMultiOptions( $tier_options )
                ->setValue( $config->updatetier )
                ->setAttrib('class', 'span4')
                ->setAttrib('title', $translate->_("Tier of addon to install"))
                ->setAttrib('data-content', $translate->_("What type of addon release would you like to install?"));
        if ($restrictToFrozen) {
            $type->setAttrib('readonly', 'readonly');
        }
        $this->addElement( $type );

        $force_reinstall = new Zend_Form_Element_Checkbox('force_reinstall');
        $force_reinstall->setLabel($translate->_('Force a reinstall even if the system is up to date.'))
                            ->setAttrib('title', $translate->_("Reinstall addon"))
                            ->setAttrib('data-content', $translate->_("Reinstalls the addon (excluding config)"));
        $this->addElement( $force_reinstall );

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel($translate->_('Click to upgrade'));
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
