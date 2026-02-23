<?php

namespace App\Admin\Controllers;

use App\Models\User;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistered;

class UserController extends AdminController
{
    protected $title = 'Users';

    protected function grid()
    {
        $grid = new Grid(new User());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Name'));
        $grid->column('email', __('Email'));
        
        $grid->column('roles', __('Roles'))->display(function ($roles) {
            return collect($roles)->pluck('name')->implode(', ');
        });
        


        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Name'));
        $show->field('email', __('Email'));
        $show->field('roles', __('Roles'))->as(function ($roles) {
            return $roles->pluck('name')->implode(', ');
        });
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new User());
    
        $form->text('name', __('Name'))->rules('required');
        $form->email('email', __('Email'))->rules('required|email|unique:users,email');
        

        
        // Auto-generate password (won't be shown in form)
        $form->hidden('password')->default(function ($form) {
            return $this->generatePassword();
        });
        
        $form->multipleSelect('roles', __('Roles'))
            ->options(Role::all()->pluck('name', 'id'))
            ->rules('required');
    
        $form->saving(function (Form $form) {
            // If password is empty, generate one
            if (empty($form->password)) {
                $form->password = $this->generatePassword();
            } else {
                $form->password = bcrypt($form->password); // Hash the password
            }
        });
    
        $form->saved(function (Form $form) {
            // Send email notification after user is saved
            $user = $form->model();
            $plainPassword = $this->generatePassword(); // Generate password again for email
            
            // Send email with the plain password
            Mail::to($user->email)->send(new UserRegistered($user, $plainPassword));
            
            // Save the plain password in the model, if necessary
            $user->password = bcrypt($plainPassword);
            $user->save();
        });
    
        return $form;
    }
    
    protected function generatePassword()
    {
        return Str::random(8);
    }
}
