<?php
/**
 * Mediego
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Mediego
 * @package    Mediego_Recommendations
 * @author     Orinoko <contact@orinoko.fr>
 * @copyright  Copyright (c) 2014-2015 Mediego (http://mediego.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Mediego recommendations
 *
 * @category   Mediego
 * @package    Mediego_Recommendations
 * @author     Orinoko <contact@orinoko.fr>
 */
class Mediego_Recommendations_Model_Collection_Memory extends Varien_Data_Collection
{
    private $_rawItems = array();
    private $_rowMapper = null;
    private $_filterIncrement = 0;
    private $_filterBrackets = array();
    private $_filterEvalRendered = '';

    public function __construct($rawItems, $rowMapper = null)
    {
        parent::__construct();

        $this->_rawItems = $rawItems;
        $this->_rowMapper = $rowMapper;
    }

    /**
     * Launch data collecting
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return Mediego_Recommendations_Model_Collection_Memory
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        $this->_generateAndFilterAndSort('_rawItems');

        // calculate totals
        $this->_totalRecords = count($this->_rawItems);
        $this->_setIsLoaded();

        // paginate and add items
        $from = ($this->getCurPage() - 1) * $this->getPageSize();
        $to = $from + $this->getPageSize() - 1;
        $isPaginated = $this->getPageSize() > 0;

        $cnt = 0;
        foreach ($this->_rawItems as $row) {
            $cnt++;
            if ($isPaginated && ($cnt < $from + 1 || $cnt > $to + 1)) {
                continue;
            }
            $item = new $this->_itemObjectClass();
            $this->addItem($item->addData($row));
            if (!$item->hasId()) {
                $item->setId($cnt);
            }
        }

        return $this;
    }

    /**
     * With specified collected items:
     *  - generate data
     *  - apply filters
     *  - sort
     */
    private function _generateAndFilterAndSort($attributeName)
    {
        // generate custom data (as rows with columns) basing on the filenames
        foreach ($this->$attributeName as $key => $rawItem) {
            $this->{$attributeName}[$key] = $this->_generateRow($rawItem);
        }

        // apply filters on generated data
        if (!empty($this->_filters)) {
            foreach ($this->$attributeName as $key => $row) {
                if (!$this->_filterRow($row)) {
                    unset($this->{$attributeName}[$key]);
                }
            }
        }

        // sort (keys are lost!)
        if (!empty($this->_orders)) {
            usort($this->$attributeName, array($this, '_usort'));
        }
    }

    /**
     * Callback for sorting items
     * Currently supports only sorting by one column
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _usort($a, $b)
    {
        foreach ($this->_orders as $key => $direction) {
            $result = $a[$key] > $b[$key] ? 1 : ($a[$key] < $b[$key] ? -1 : 0);
            return (self::SORT_ORDER_ASC === strtoupper($direction) ? $result : -$result);
            break;
        }
    }

    /**
     * Set select order
     * Currently supports only sorting by one column
     *
     * @param   string $field
     * @param   string $direction
     * @return  Varien_Data_Collection
     */
    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $this->_orders = array($field => $direction);
        return $this;
    }

    /**
     * Generate item row based on the value
     *
     * @param mixed $rawItem
     * @return array
     */
    protected function _generateRow($rawItem)
    {
        if (!$this->_rowMapper) {
            throw new Exception('Please specify a row mapper or override _generateRow');
        }

        return call_user_func($this->_rowMapper, $rawItem);
    }

    /**
     * Set a custom filter with callback
     * The callback must take 3 params:
     *     string $field       - field key,
     *     mixed  $filterValue - value to filter by,
     *     array  $row         - a generated row (before generaring varien objects)
     *
     * @param string $field
     * @param mixed $value
     * @param string $type 'and'|'or'
     * @param callback $callback
     * @param bool $isInverted
     * @return Mediego_Recommendations_Model_Collection_Memory
     */
    public function addCallbackFilter($field, $value, $type, $callback, $isInverted = false)
    {
        $this->_filters[$this->_filterIncrement] = array(
            'field'       => $field,
            'value'       => $value,
            'is_and'      => 'and' === $type,
            'callback'    => $callback,
            'is_inverted' => $isInverted
        );
        $this->_filterIncrement++;
        return $this;
    }

    /**
     * The filters renderer and caller
     * Aplies to each row, renders once.
     *
     * @param array $row
     * @return bool
     */
    protected function _filterRow($row)
    {
        // render filters once
        if (!$this->_isFiltersRendered) {
            $eval = '';
            for ($i = 0; $i < $this->_filterIncrement; $i++) {
                if (isset($this->_filterBrackets[$i])) {
                    $eval .= $this->_renderConditionBeforeFilterElement($i, $this->_filterBrackets[$i]['is_and'])
                        . $this->_filterBrackets[$i]['value'];
                }
                else {
                    $f = '$this->_filters[' . $i . ']';
                    $eval .= $this->_renderConditionBeforeFilterElement($i, $this->_filters[$i]['is_and'])
                        . ($this->_filters[$i]['is_inverted'] ? '!' : '')
                        . '$this->_invokeFilter(' . "{$f}['callback'], array({$f}['field'], {$f}['value'], " . '$row))';
                }
            }
            $this->_filterEvalRendered = $eval;
            $this->_isFiltersRendered = true;
        }
        $result = false;
        if ($this->_filterEvalRendered) {
            eval('$result = ' . $this->_filterEvalRendered . ';');
        }
        return $result;
    }

    /**
     * Invokes specified callback
     * Skips, if there is no filtered key in the row
     *
     * @param callback $callback
     * @param array $callbackParams
     * @return bool
     */
    protected function _invokeFilter($callback, $callbackParams)
    {
        list($field, $value, $row) = $callbackParams;
        if (!array_key_exists($field, $row)) {
            return false;
        }
        return call_user_func_array($callback, $callbackParams);
    }

    /**
     * Fancy field filter
     *
     * @param string $field
     * @param mixed $cond
     * @param string $type 'and' | 'or'
     * @see Varien_Data_Collection_Db::addFieldToFilter()
     * @return Mediego_Recommendations_Model_Collection_Memory
     */
    public function addFieldToFilter($field, $cond, $type = 'and')
    {
        $inverted = true;

        // simply check whether equals
        if (!is_array($cond)) {
            return $this->addCallbackFilter($field, $cond, $type, array($this, 'filterCallbackEq'));
        }

        // versatile filters
        if (isset($cond['from']) || isset($cond['to'])) {
            $this->_addFilterBracket('(', 'and' === $type);
            if (isset($cond['from'])) {
                $this->addCallbackFilter($field, $cond['from'], 'and', array($this, 'filterCallbackIsLessThan'), $inverted);
            }
            if (isset($cond['to'])) {
                $this->addCallbackFilter($field, $cond['to'], 'and', array($this, 'filterCallbackIsMoreThan'), $inverted);
            }
            return $this->_addFilterBracket(')');
        }
        if (isset($cond['eq'])) {
            return $this->addCallbackFilter($field, $cond['eq'], $type, array($this, 'filterCallbackEq'));
        }
        if (isset($cond['neq'])) {
            return $this->addCallbackFilter($field, $cond['neq'], $type, array($this, 'filterCallbackEq'), $inverted);
        }
        if (isset($cond['like'])) {
            return $this->addCallbackFilter($field, $cond['like'], $type, array($this, 'filterCallbackLike'));
        }
        if (isset($cond['nlike'])) {
            return $this->addCallbackFilter($field, $cond['nlike'], $type, array($this, 'filterCallbackLike'), $inverted);
        }
        if (isset($cond['in'])) {
            return $this->addCallbackFilter($field, $cond['in'], $type, array($this, 'filterCallbackInArray'));
        }
        if (isset($cond['nin'])) {
            return $this->addCallbackFilter($field, $cond['nin'], $type, array($this, 'filterCallbackInArray'), $inverted);
        }
        if (isset($cond['notnull'])) {
            return $this->addCallbackFilter($field, $cond['notnull'], $type, array($this, 'filterCallbackIsNull'), $inverted);
        }
        if (isset($cond['null'])) {
            return $this->addCallbackFilter($field, $cond['null'], $type, array($this, 'filterCallbackIsNull'));
        }
        if (isset($cond['moreq'])) {
            return $this->addCallbackFilter($field, $cond['moreq'], $type, array($this, 'filterCallbackIsLessThan'), $inverted);
        }
        if (isset($cond['gt'])) {
            return $this->addCallbackFilter($field, $cond['gt'], $type, array($this, 'filterCallbackIsMoreThan'));
        }
        if (isset($cond['lt'])) {
            return $this->addCallbackFilter($field, $cond['lt'], $type, array($this, 'filterCallbackIsLessThan'));
        }
        if (isset($cond['gteq'])) {
            return $this->addCallbackFilter($field, $cond['gteq'], $type, array($this, 'filterCallbackIsLessThan'), $inverted);
        }
        if (isset($cond['lteq'])) {
            return $this->addCallbackFilter($field, $cond['lteq'], $type, array($this, 'filterCallbackIsMoreThan'), $inverted);
        }
        if (isset($cond['finset'])) {
            $filterValue = ($cond['finset'] ? explode(',', $cond['finset']) : array());
            return $this->addCallbackFilter($field, $filterValue, $type, array($this, 'filterCallbackInArray'));
        }

        // add OR recursively
        foreach ($cond as $orCond) {
            $this->_addFilterBracket('(', 'and' === $type);
            $this->addFieldToFilter($field, $orCond, 'or');
            $this->_addFilterBracket(')');
        }
        return $this;
    }

    /**
     * Prepare a bracket into filters
     *
     * @param string $bracket
     * @param bool $isAnd
     * @return Mediego_Recommendations_Model_Collection_Memory
     */
    protected function _addFilterBracket($bracket = '(', $isAnd = true)
    {
        $this->_filterBrackets[$this->_filterIncrement] = array(
            'value' => $bracket === ')' ? ')' : '(',
            'is_and' => $isAnd,
        );
        $this->_filterIncrement++;
        return $this;
    }

    /**
     * Render condition sign before element, if required
     *
     * @param int $increment
     * @param bool $isAnd
     * @return string
     */
    protected function _renderConditionBeforeFilterElement($increment, $isAnd)
    {
        if (isset($this->_filterBrackets[$increment]) && ')' === $this->_filterBrackets[$increment]['value']) {
            return '';
        }
        $prevIncrement = $increment - 1;
        $prevBracket = false;
        if (isset($this->_filterBrackets[$prevIncrement])) {
            $prevBracket = $this->_filterBrackets[$prevIncrement]['value'];
        }
        if ($prevIncrement < 0 || $prevBracket === '(') {
            return '';
        }
        return ($isAnd ? ' && ' : ' || ');
    }

    /**
     * Does nothing. Intentionally disabled parent method
     *
     * @return Mediego_Recommendations_Model_Collection_Memory
     */
    public function addFilter($field, $value, $type = 'and')
    {
        return $this;
    }

    /**
     * Get all ids of collected items
     *
     * @return array
     */
    public function getAllIds()
    {
        return array_keys($this->_items);
    }

    /**
     * Callback method for 'like' fancy filter
     *
     * @param string $field
     * @param mixed $filterValue
     * @param array $row
     * @return bool
     * @see addFieldToFilter()
     * @see addCallbackFilter()
     */
    public function filterCallbackLike($field, $filterValue, $row)
    {
        // [ORK] Remove the quoting of the filter value, if any
        $filterValue = trim($filterValue, "'");

        $filterValueRegex = str_replace('%', '(.*?)', preg_quote($filterValue, '/'));

        return (bool)preg_match("/^{$filterValueRegex}$/i", $row[$field]);
    }

    /**
     * Callback method for 'eq' fancy filter
     *
     * @param string $field
     * @param mixed $filterValue
     * @param array $row
     * @return bool
     * @see addFieldToFilter()
     * @see addCallbackFilter()
     */
    public function filterCallbackEq($field, $filterValue, $row)
    {
        return $filterValue == $row[$field];
    }

    /**
     * Callback method for 'in' fancy filter
     *
     * @param string $field
     * @param mixed $filterValue
     * @param array $row
     * @return bool
     * @see addFieldToFilter()
     * @see addCallbackFilter()
     */
    public function filterCallbackInArray($field, $filterValue, $row)
    {
        return in_array($row[$field], $filterValue);
    }

    /**
     * Callback method for 'isnull' fancy filter
     *
     * @param string $field
     * @param mixed $filterValue
     * @param array $row
     * @return bool
     * @see addFieldToFilter()
     * @see addCallbackFilter()
     */
    public function filterCallbackIsNull($field, $filterValue, $row)
    {
        return null === $row[$field];
    }

    /**
     * Callback method for 'moreq' fancy filter
     *
     * @param string $field
     * @param mixed $filterValue
     * @param array $row
     * @return bool
     * @see addFieldToFilter()
     * @see addCallbackFilter()
     */
    public function filterCallbackIsMoreThan($field, $filterValue, $row)
    {
        return $row[$field] > $filterValue;
    }

    /**
     * Callback method for 'lteq' fancy filter
     *
     * @param string $field
     * @param mixed $filterValue
     * @param array $row
     * @return bool
     * @see addFieldToFilter()
     * @see addCallbackFilter()
     */
    public function filterCallbackIsLessThan($field, $filterValue, $row)
    {
        return $row[$field] < $filterValue;
    }
}
