<?php
/**
 * Table View Helper
 */
class Zend_View_Helper_Table extends Zend_View_Helper_Placeholder_Container_Standalone
{

    /**
     * @var string registry key
     */
	protected $_regKey = 'App_View_Helper_Table';

    /**
     * Table caption
     *
     * @var string
     */
	protected $_caption = array();

    /**
     * Table summary
     *
     * @var string
     */
    protected $_summary;

    /**
     * Table columns
     *
     * @var array
     */
    protected $_columns = array();

    /**
     * Appended table content
     *
     * @var string
     */
    protected $_content;

    /**
     * Cell templates
     *
     * @var array
     */
    protected $_cellContent = array();

    /**
     * Rows
     *
     * @var array
     */
    protected $_rows = array();

    /**
     * Message of the empty table
     *
     * @var string
     */
    protected $_emptyRowContent;

    /**
     * Footer content
     *
     * @var string
     */
    protected $_footer;

    /**
     * Table attributes
     *
     * @var array
     */
    protected $_attributes;


    public function table(array $columns = null, array $rows = null)
    {
        if ($columns) {
            $this->setColumns($columns);
        }
        if ($rows) {
            $this->setRows($rows);
        }

    	return $this;
    }

    /**
     * Set table caption
     *
     * @param string $caption
     * @return App_View_Helper_Table
     */
    public function setCaption($caption)
    {
        $this->_caption = $caption;
        return $this;
    }

    /**
     * Set table columns
     *
     * @return App_View_Helper_Table
     */
    public function setColumns(array $columns)
    {
        $this->_columns = $columns;

        return $this;
    }

    /**
     * Set table summary
     * 
     * @param string $summary
     * @return App_View_Helper_Table
     */
    public function setSummary($summary)
    {
        $this->_summary = $summary;
        return $this;
    }

    /**
     * Set message that will be shown when table has no rows
     *
     * @param string $str
     * @return App_View_Helper_Table
     */
    public function setEmptyRowContent($str)
    {
        $this->_emptyRowContent = $str;
        return $this;
    }

    /**
     * Add arbitrary text content inside table
     *
     * @param string $str
     * @return App_View_Helper_Table
     */
    public function setContent($str)
    {
        $this->_content .= $str;
        return $this;
    }

    /**
     * Add row
     *
     * @param array $cols
     * @return App_View_Helper_Table
     */
    public function addRow($cols)
    {
        if (is_array($cols)) {
            array_push($this->_rows, $cols);
        }
        elseif (is_object($cols) && method_exists($cols, 'toArray')) {
            array_push($this->_rows, $cols->toArray());
        }

        return $this;
    }

    /**
     *
     *
     * @param array|Zend_Db_Table_Rowset $rows
     * @return App_View_Helper_Table
     */
    public function setRows($rows)
    {
        if (count($rows)) {
            foreach ($rows as $r) {
                $this->addRow($r);
            }
        }
        return $this;
    }

    /**
     * Set cell content. All row variables should be used inside brackets
     *
     * @param string $str - String that will be used as a template
     * @param string|number $position - Key of the column or its count
     * @return App_View_Helper_Table
     */
    public function setCellContent($str, $position)
    {
        $this->_cellContent[$position] = $str;
        return $this;
    }

    /**
     * Set footer content
     *
     * @param string $footer
     * @return App_View_Helper_Table
     */
    public function setFooter($footer)
    {
        $this->_footer = $footer;
        return $this;
    }

    /**
     * Set table Attributes
     * @param array $attribs
     * @return App_View_Helper_Table
     */
    public function setAttributes(array $attribs)
    {
        $this->_attributes = $attribs;
        return $this;
    }

    /**
     * Render link elements as string
     *
     * @return string
     */
    public function toString()
    {
        $xhtml = $this->getPrefix();

        $xhtml .= '<table ';
        if (count($this->_attributes)) {
            foreach ($this->_attributes as $key=>$value) {
                $xhtml .= $key.'="'.$value.'" ';
            }
        }
        $xhtml .= 'cellspacing="0" '.(($this->_summary) ? 'summary="'.$this->_summary.'"' : '').'>';
        if ($this->_caption) {
            $xhtml .= '<caption>'.$this->_caption.'</caption>';
        }

        if (count($this->_columns)) {
            $xhtml .= '<thead><tr>';

            $bIsFirst = true;
            foreach ($this->_columns as $key=>$value) {
                $xhtml .= '<th'.(($bIsFirst) ? ' class="first_column"':'').'>'.$value.'</th>';
                $bIsFirst = false;
            }

            $xhtml .= '</tr></thead>';
        }

        $xhtml .= '<tbody>';

        if (count($this->_rows)) {

            $columns = (count($this->_columns) > 0) ? array_keys($this->_columns) : array_keys($this->_rows[0]);

            $counter = 0;
            foreach ($this->_rows as $r) {
                $counter++;
                $xhtml .= ($counter % 2) ? '<tr class="alt">' : '<tr>';

                $i = 0;
                foreach ($columns as $key) {
                    $xhtml .= '<td'.(($i == 0) ? ' class="first_column"':'').'>';

                    if (isset($this->_cellContent[$key])) {

                        $this->_currentRow = $r;    // reference current row for _replacePlaceholders callback
                        if (function_exists($this->_cellContent[$key])) {
                            $xhtml .= $this->_cellContent[$key]($this->_currentRow);
                        }
                        else {
                            $xhtml .= preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', array($this, "_replacePlaceholders"), stripslashes($this->_cellContent[$key]));
                        }
                    }
                    elseif (isset($r[$key])) {
                        $xhtml .= stripslashes($r[$key]);
                    }
                    elseif (isset($r[$i])) {
                        $xhtml .= stripslashes($r[$i]);
                    }
                    

                    $xhtml .= '</td>';
                    $i++;
                }
                $this->_currentRow = null;
                $xhtml .= '</tr>';
            }

            if ($this->_content) {
                $xhtml .= $this->_content;
            }

        }
        else {
            $xhtml .= '<tr><td class="first_column" colspan="'.count($this->_columns).'">'.$this->_emptyRowContent.'</td></tr>';
        }

        $xhtml .= '</tbody>';

        if ($this->_footer) {
            $xhtml .= '<tfoot>';
            $xhtml .= '<tr>';
            if (is_array($this->_footer)) {
                foreach ($this->_footer as $key=>$cell) {
                    $xhtml .= '<td'.(($counter == 0) ? ' class="first_column"' : '').'>'.$cell.'</td>';
                }
            }
            else {
                $xhtml .= '<td class="first_column" colspan="'.count($this->_columns).'">'.$this->_footer.'</td>';
            }
            $xhtml .= '</tr>';
            $xhtml .= '</tfoot>';
        }
        $xhtml .= '</table>';

        return $xhtml;
    }

    /**
     * Function used to replace string placeholders
     *
     * @param array $matches
     * @return string
     */
    private function _replacePlaceholders($matches)
    {
        return (isset($this->_currentRow[$matches[1]])) ? $this->_currentRow[$matches[1]] : '';
    }

}

