<?php

namespace Oro\Bundle\GridBundle\Datagrid;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Form;
use Sonata\AdminBundle\Filter\FilterInterface as SonataFilterInterface;
use Oro\Bundle\GridBundle\Filter\FilterInterface;
use Oro\Bundle\GridBundle\Field\FieldDescriptionCollection;
use Oro\Bundle\GridBundle\Sorter\SorterInterface;

class Datagrid implements DatagridInterface
{
    /**
     * @var ProxyQueryInterface
     */
    protected $query;

    /**
     * @var FieldDescriptionCollection
     */
    protected $columns;

    /**
     * @var PagerInterface
     */
    protected $pager;

    /**
     * @var FormBuilderInterface
     */
    protected $formBuilder;

    /**
     * Parameters applied flag
     *
     * @var bool
     */
    protected $parametersApplied = false;

    /**
     * @var ParametersInterface
     */
    protected $parameters;

    /**
     * @var array
     */
    protected $filters;

    /**
     * @var SorterInterface[]
     */
    protected $sorters = array();

    /**
     * @var Form
     */
    protected $form;

    /**
     * @param ProxyQueryInterface $query
     * @param FieldDescriptionCollection $columns
     * @param PagerInterface $pager
     * @param FormBuilderInterface $formBuilder
     * @param ParametersInterface $parameters
     */
    public function __construct(
        ProxyQueryInterface $query,
        FieldDescriptionCollection $columns,
        PagerInterface $pager,
        FormBuilderInterface $formBuilder,
        ParametersInterface $parameters
    ) {
        // TODO Empty $parameters is not acceptible, maybe create default value instead (null object pattern?)
        $this->query       = $query;
        $this->columns     = $columns;
        $this->pager       = $pager;
        $this->formBuilder = $formBuilder;
        $this->parameters  = $parameters;
    }

    /**
     * @param SonataFilterInterface $filter
     * @return void
     */
    public function addFilter(SonataFilterInterface $filter)
    {
        $this->filters[$filter->getName()] = $filter;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param string $name
     *
     * @return SonataFilterInterface
     */
    public function getFilter($name)
    {
        return $this->hasFilter($name) ? $this->filters[$name] : null;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasFilter($name)
    {
        return isset($this->filters[$name]);
    }

    /**
     * @param string $name
     */
    public function removeFilter($name)
    {
        unset($this->filters[$name]);
    }

    /**
     * @return boolean
     */
    public function hasActiveFilters()
    {
        /** @var $filter FilterInterface */
        foreach ($this->filters as $name => $filter) {
            // TODO Add method to interface
            if ($filter->isActive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param SorterInterface $sorter
     * @return void
     */
    public function addSorter(SorterInterface $sorter)
    {
        $this->sorters[$sorter->getName()] = $sorter;
    }

    /**
     * @return PagerInterface
     */
    public function getPager()
    {
        return $this->pager;
    }

    protected function applyParameters()
    {
        if ($this->parametersApplied) {
            return;
        }

        $this->applyFilters();
        $this->applySorters();
        $this->applyPager();

        $this->parametersApplied = true;
    }

    /**
     * Apply filter data to ProxyQuery and add form fields
     */
    protected function applyFilters()
    {
        /** @var $filter Oro\Bundle\GridBundle\Filter\FilterInterface */
        foreach ($this->getFilters() as $name => $filter) {
            $filter->apply($this->query, array('value' => $this->parameters->get($name)));

            list($type, $options) = $filter->getRenderSettings();
            $this->formBuilder->add($filter->getFormName(), $type, $options);
        }
    }

    /**
     * Add sorters on grid and apply requested sorting
     */
    protected function applySorters()
    {
        // we should retain an order in which sorters were added

        $sortBy = $this->parameters->get('_sort_by');
        $sortOrder = $this->parameters->get('_sort_order');

        $requestedSorters = array_combine(
            is_array($sortBy) ? $sortBy : array($sortBy),
            is_array($sortOrder) ? $sortOrder : array($sortOrder)
        );

        foreach ($requestedSorters as $fieldName => $direction) {
            if (isset($this->sorters[$fieldName])) {
                $this->sorters[$fieldName]->apply($this->query, $direction);
            }
        }

        // TODO: add sorters in form builder
    }

    protected function applyPager()
    {
        // TODO Be able to configure parameters names
        $this->pager->setPage($this->parameters->get('_page', 1));
        $this->pager->setMaxPerPage($this->parameters->get('_per_page', 10));
        $this->pager->init();

        $this->formBuilder->add('_page', 'hidden');
        $this->formBuilder->add('_per_page', 'hidden');
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        $this->applyParameters();
        return $this->form;
    }

    /**
     * @return ProxyQueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        $this->applyParameters();
        return $this->getQuery()->execute();
    }

    /**
     * @deprecated Use applyParameters instead
     * @return void
     */
    public function buildPager()
    {
        $this->applyParameters();
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        // TODO Interface declare array return type
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($name, $operator, $value)
    {
        // TODO: Implement setValue() method.
    }

    /**
     * @return array
     */
    public function getValues()
    {
        // TODO: Implement getValues() method.
    }
}
