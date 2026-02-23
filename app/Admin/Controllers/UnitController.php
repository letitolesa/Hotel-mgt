<?php

namespace App\Admin\Controllers;

use App\Models\Unit;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class UnitController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Unit Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Unit());

        $grid->column('id', 'ID')->sortable();
        $grid->column('code', 'Unit Code')->sortable()->badge('primary');
        $grid->column('name', 'Unit Name')->sortable();
        $grid->column('symbol', 'Symbol')->sortable()->badge('info');
      

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('code', 'Code');
            $filter->like('name', 'Name');
            $filter->like('symbol', 'Symbol');
        });

        // Quick search
        $grid->quickSearch('code', 'name', 'symbol');

        // Disable batch delete
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });

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
        $show = new Show(Unit::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('code', 'Unit Code');
        $show->field('name', 'Unit Name');
        $show->field('symbol', 'Symbol');
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        // Show related menu item ingredients if any
        $show->menuItemIngredients('Used in Menu Items', function ($ingredients) {
            $ingredients->resource('/admin/menu-item-ingredients');
            $ingredients->menuItem()->name();
            $ingredients->quantity();
            $ingredients->wastage_percent();
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Unit());

        // Unit code
        $form->text('code', 'Unit Code')
            ->required()
            ->rules('required|string|min:1|max:20|unique:units,code,{{id}}|regex:/^[A-Z0-9_]+$/')
            ->help('Unique code (1-20 characters, uppercase letters, numbers, underscore only)')
            ->placeholder('e.g., KG, LTR, PCS');

        // Unit name
        $form->text('name', 'Unit Name')
            ->required()
            ->rules('required|string|min:2|max:100|unique:units,name,{{id}}')
            ->help('Full unit name (2-100 characters)')
            ->placeholder('e.g., Kilogram, Liter, Pieces');

        // Unit symbol
        $form->text('symbol', 'Symbol')
            ->required()
            ->rules('required|string|min:1|max:10')
            ->help('Unit symbol (1-10 characters)')
            ->placeholder('e.g., kg, l, pcs');

        // Display timestamps in edit form
        if ($form->isEditing()) {
            $form->divider();
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        }

        return $form;
    }
}