<?php

namespace App\Admin\Controllers;

use App\Models\TaxRate;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class TaxRateController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Tax Rate Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TaxRate());

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', 'Tax Name')->sortable();
        $grid->column('rate', 'Rate (%)')->display(function ($rate) {
            return number_format($rate, 2) . '%';
        })->sortable()->label('primary');
        $grid->column('created_at', 'Created')->display(function ($date) {
            if (!$date) {
                return '-';
            }
            // Handle both string and DateTime/Carbon objects
            if (is_string($date)) {
                return date('Y-m-d', strtotime($date));
            }
            return $date->format('Y-m-d');
        })->sortable();

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('name', 'Tax Name');
            $filter->between('rate', 'Rate (%)');
        });

        // Quick search
        $grid->quickSearch('name');

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(TaxRate::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('name', 'Tax Name');
        $show->field('rate', 'Rate (%)')->as(function ($rate) {
            return number_format($rate, 2) . '%';
        });
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new TaxRate());

        // Tax Name
        $form->text('name', 'Tax Name')
            ->required()
            ->rules('required|string|min:3|max:100|unique:tax_rates,name,{{id}}')
            ->help('Tax name (3-100 characters)')
            ->placeholder('e.g., VAT, GST, Sales Tax');

        // Tax Rate
        $form->decimal('rate', 'Rate (%)')
            ->required()
            ->rules('required|numeric|min:0|max:100')
            ->help('Tax rate percentage (0-100)')
            ->placeholder('e.g., 15.00');

        // Display timestamps in edit mode
        if ($form->isEditing()) {
            $form->divider();
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        }

        return $form;
    }
}