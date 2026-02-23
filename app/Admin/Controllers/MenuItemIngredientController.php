<?php

namespace App\Admin\Controllers;

use App\Models\MenuItemIngredient;
use App\Models\MenuItem;
use App\Models\InventoryItem;
use App\Models\Unit;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class MenuItemIngredientController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Menu Item Ingredients';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MenuItemIngredient());

        $grid->column('id', 'ID')->sortable();
        $grid->column('menuItem.name', 'Menu Item')->sortable();
        $grid->column('inventoryItem.name', 'Ingredient')->sortable();
        $grid->column('quantity', 'Quantity')->display(function ($qty) {
            return number_format($qty, 2);
        })->sortable();
        $grid->column('unit.symbol', 'Unit')->sortable();
        $grid->column('wastage_percent', 'Wastage %')->display(function ($percent) {
            return $percent ? number_format($percent, 2) . '%' : '-';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->equal('menu_item_id', 'Menu Item')->select(MenuItem::pluck('name', 'id'));
            $filter->equal('inventory_item_id', 'Ingredient')->select(InventoryItem::pluck('name', 'id'));
            $filter->equal('unit_id', 'Unit')->select(Unit::pluck('name', 'id'));
        });

        // Quick search
        $grid->quickSearch(function ($model, $query) {
            $model->whereHas('menuItem', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })->orWhereHas('inventoryItem', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            });
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
        $show = new Show(MenuItemIngredient::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('menuItem.name', 'Menu Item');
        $show->field('inventoryItem.name', 'Ingredient');
        $show->field('quantity', 'Quantity')->as(function ($qty) {
            return number_format($qty, 2);
        });
        $show->field('unit.name', 'Unit');
        $show->field('wastage_percent', 'Wastage %')->as(function ($percent) {
            return $percent ? $percent . '%' : '0%';
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
        $form = new Form(new MenuItemIngredient());

        // Menu Item
        $form->select('menu_item_id', 'Menu Item')
            ->options(MenuItem::pluck('name', 'id'))
            ->required()
            ->rules('required|integer|exists:menu_items,id')
            ->help('Select the menu item');

        // Ingredient
        $form->select('inventory_item_id', 'Ingredient')
            ->options(InventoryItem::where('is_active', true)->pluck('name', 'id'))
            ->required()
            ->rules('required|integer|exists:inventory_items,id')
            ->help('Select the ingredient');

        // Quantity
        $form->decimal('quantity', 'Quantity')
            ->required()
            ->rules('required|numeric|min:0|max:999999.99')
            ->help('Quantity required')
            ->placeholder('0.00');

        // Unit
        $form->select('unit_id', 'Unit')
            ->options(Unit::pluck('symbol', 'id'))
            ->required()
            ->rules('required|integer|exists:units,id')
            ->help('Select unit of measurement');

        // Wastage
        $form->decimal('wastage_percent', 'Wastage %')
            ->default(0)
            ->rules('nullable|numeric|min:0|max:100')
            ->help('Wastage percentage (0-100)')
            ->placeholder('0');

        // Display timestamps in edit mode
        if ($form->isEditing()) {
            $form->divider();
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        }

        // Prevent duplicate ingredient per menu item
        $form->saving(function (Form $form) {
            $exists = MenuItemIngredient::where('menu_item_id', $form->menu_item_id)
                ->where('inventory_item_id', $form->inventory_item_id)
                ->when($form->model()->id, function ($query) use ($form) {
                    return $query->where('id', '!=', $form->model()->id);
                })
                ->exists();

            if ($exists) {
                throw new \Exception('This ingredient is already added to the menu item.');
            }
        });

        return $form;
    }
}