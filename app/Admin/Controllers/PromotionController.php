<?php

namespace App\Admin\Controllers;

use App\Models\Promotion;
use App\Models\User;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class PromotionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Promotion Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Promotion());

        $grid->column('id', 'ID')->sortable();
        $grid->column('code', 'Promo Code')->sortable()->badge('primary');
        $grid->column('name', 'Promotion Name')->sortable();
        $grid->column('type', 'Type')->display(function ($type) {
            $types = [
                'percentage' => 'Percentage',
                'fixed_amount' => 'Fixed Amount',
                'buy_x_get_y' => 'Buy X Get Y',
                'free_shipping' => 'Free Shipping',
                'loyalty_points' => 'Loyalty Points'
            ];
            return $types[$type] ?? $type;
        })->label('info');
        $grid->column('value', 'Value')->display(function ($value) use ($grid) {
            if ($this->type === 'percentage') {
                return $value . '%';
            }
            return 'ETB ' . number_format($value, 2);
        });
        $grid->column('start_date', 'Valid From')->display(function ($date) {
            return $date ? $date->format('Y-m-d') : '-';
        })->sortable();
        $grid->column('end_date', 'Valid To')->display(function ($date) {
            return $date ? $date->format('Y-m-d') : '-';
        })->sortable();
        $grid->column('status', 'Status')->display(function () {
            $now = now();
            if (!$this->is_active) {
                return '<span class="label label-default">Inactive</span>';
            }
            if ($this->start_date > $now) {
                return '<span class="label label-info">Upcoming</span>';
            }
            if ($this->end_date < $now) {
                return '<span class="label label-danger">Expired</span>';
            }
            return '<span class="label label-success">Active</span>';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('code', 'Promo Code');
            $filter->like('name', 'Promotion Name');
            $filter->equal('type', 'Type')->select([
                'percentage' => 'Percentage',
                'fixed_amount' => 'Fixed Amount',
                'buy_x_get_y' => 'Buy X Get Y',
                'free_shipping' => 'Free Shipping',
                'loyalty_points' => 'Loyalty Points'
            ]);
            $filter->between('start_date', 'Start Date')->date();
            $filter->between('end_date', 'End Date')->date();
            $filter->equal('is_active', 'Active')->select([
                '' => 'All',
                1 => 'Yes',
                0 => 'No'
            ]);
        });

        // Quick search
        $grid->quickSearch('code', 'name');

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
        $show = new Show(Promotion::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('code', 'Promo Code');
        $show->field('name', 'Promotion Name');
        $show->field('description', 'Description');
        
        $show->field('type', 'Type')->using([
            'percentage' => 'Percentage',
            'fixed_amount' => 'Fixed Amount',
            'buy_x_get_y' => 'Buy X Get Y',
            'free_shipping' => 'Free Shipping',
            'loyalty_points' => 'Loyalty Points'
        ]);
        
        $show->field('value', 'Value')->as(function ($value) {
            if ($this->type === 'percentage') {
                return $value . '%';
            }
            return 'ETB ' . number_format($value, 2);
        });
        
        $show->field('min_order_amount', 'Minimum Order')->as(function ($amount) {
            return $amount ? 'ETB ' . number_format($amount, 2) : 'No minimum';
        });
        
        $show->field('max_discount_amount', 'Maximum Discount')->as(function ($amount) {
            return $amount ? 'ETB ' . number_format($amount, 2) : 'No maximum';
        });
        
        $show->field('usage_limit', 'Usage Limit')->as(function ($limit) {
            return $limit ?: 'Unlimited';
        });
        
        $show->field('usage_per_customer', 'Per Customer Limit')->as(function ($limit) {
            return $limit ?: 'Unlimited';
        });
        
        $show->field('start_date', 'Start Date');
        $show->field('end_date', 'End Date');
        
        $show->field('days_of_week', 'Valid Days')->unescape()->as(function ($days) {
            if (empty($days) || !is_array($days)) {
                return 'All days';
            }
            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $validDays = [];
            foreach ($days as $day) {
                $validDays[] = $dayNames[$day] ?? $day;
            }
            return implode(', ', $validDays);
        });
        
        $show->field('applicable_to', 'Applicable To')->using([
            'all' => 'All Items',
            'menu_items' => 'Specific Menu Items',
            'categories' => 'Menu Categories',
            'customers' => 'Specific Customers'
        ]);
        
        $show->field('is_active', 'Active')->using([1 => 'Yes', 0 => 'No']);
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
        $form = new Form(new Promotion());

        // Basic Information
        $form->tab('Basic Information', function ($form) {
            // Promo Code
            $form->text('code', 'Promo Code')
                ->required()
                ->rules('required|string|min:3|max:50|unique:promotions,code,{{id}}|regex:/^[A-Z0-9_-]+$/')
                ->help('Unique promo code (3-50 characters, uppercase, numbers, underscore, hyphen)')
                ->placeholder('e.g., SUMMER2024, WELCOME10');

            // Promotion Name
            $form->text('name', 'Promotion Name')
                ->required()
                ->rules('required|string|min:3|max:255')
                ->help('Promotion name (3-255 characters)')
                ->placeholder('e.g., Summer Sale 2024');

            // Description
            $form->textarea('description', 'Description')
                ->rules('nullable|string|max:1000')
                ->rows(3)
                ->help('Brief description (max 1000 characters)')
                ->placeholder('Enter promotion description...');
        });

        // Discount Settings
        $form->tab('Discount Settings', function ($form) {
            // Promotion Type
            $form->select('type', 'Promotion Type')
                ->options([
                    'percentage' => 'Percentage Discount',
                    'fixed_amount' => 'Fixed Amount Discount',
                    'buy_x_get_y' => 'Buy X Get Y',
                    'free_shipping' => 'Free Shipping',
                    'loyalty_points' => 'Loyalty Points'
                ])
                ->required()
                ->rules('required|in:percentage,fixed_amount,buy_x_get_y,free_shipping,loyalty_points')
                ->help('Select the type of promotion');

            // Value
            $form->decimal('value', 'Discount Value')
                ->required()
                ->rules('required|numeric|min:0|max:999999.99')
                ->help('Discount value (percentage or fixed amount)')
                ->placeholder('0.00');

            // Minimum Order
            $form->currency('min_order_amount', 'Minimum Order Amount')
                ->symbol('ETB')
                ->rules('nullable|numeric|min:0|max:999999.99')
                ->help('Minimum order amount required (leave empty for no minimum)')
                ->placeholder('0.00');

            // Maximum Discount
            $form->currency('max_discount_amount', 'Maximum Discount Amount')
                ->symbol('ETB')
                ->rules('nullable|numeric|min:0|max:999999.99')
                ->help('Maximum discount amount (leave empty for no maximum)')
                ->placeholder('0.00');
        });

        // Validity Settings
        $form->tab('Validity', function ($form) {
            // Date Range
            $form->datetime('start_date', 'Start Date')
                ->required()
                ->rules('required|date')
                ->help('When the promotion becomes valid')
                ->default(now());

            $form->datetime('end_date', 'End Date')
                ->required()
                ->rules('required|date|after:start_date')
                ->help('When the promotion expires')
                ->default(now()->addDays(30));

            // Days of Week
            $form->multipleSelect('days_of_week', 'Valid Days')
                ->options([
                    0 => 'Sunday',
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday'
                ])
                ->help('Select days when promotion is valid (leave empty for all days)');
        });

        // Usage Limits
        $form->tab('Usage Limits', function ($form) {
            $form->number('usage_limit', 'Total Usage Limit')
                ->rules('nullable|integer|min:1')
                ->help('Maximum number of times this promotion can be used (leave empty for unlimited)')
                ->placeholder('e.g., 100');

            $form->number('usage_per_customer', 'Usage Limit Per Customer')
                ->rules('nullable|integer|min:1')
                ->help('Maximum times per customer (leave empty for unlimited)')
                ->placeholder('e.g., 1');
        });

        // Applicability
        $form->tab('Applicability', function ($form) {
            $form->radio('applicable_to', 'Applicable To')
                ->options([
                    'all' => 'All Items',
                    'menu_items' => 'Specific Menu Items',
                    'categories' => 'Menu Categories',
                    'customers' => 'Specific Customers'
                ])
                ->default('all')
                ->help('What this promotion applies to');

            // Note: applicable_ids would need dynamic loading based on applicable_to
            // This is a simplified version - in production you'd want to load options dynamically
            $form->textarea('applicable_ids', 'Applicable IDs')
                ->rules('nullable|string')
                ->help('Enter IDs separated by commas (e.g., 1,2,3)')
                ->placeholder('1,2,3');
        });

        // Status
        $form->tab('Status', function ($form) {
            $form->switch('is_active', 'Active')
                ->default(1)
                ->help('Enable or disable this promotion');

            // Display timestamps in edit mode
            if ($form->isEditing()) {
                $form->divider();
                $form->display('created_at', 'Created At');
                $form->display('updated_at', 'Updated At');
            }
        });

        // Set created_by
        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->created_by = auth()->user()->id;
            }
        });

        // Validation to ensure end_date >= start_date
        $form->saving(function (Form $form) {
            if ($form->start_date && $form->end_date) {
                $start = strtotime($form->start_date);
                $end = strtotime($form->end_date);
                if ($end < $start) {
                    throw new \Exception('End date must be after start date.');
                }
            }
        });

        return $form;
    }
}