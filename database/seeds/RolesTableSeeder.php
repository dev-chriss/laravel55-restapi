<?php

use App\User;
use jeremykenedy\LaravelRoles\Models\Role;
use jeremykenedy\LaravelRoles\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	    /**
	     * Add Roles
	     *
	     */

		if (Role::where('name', '=', 'Superadmin')->first() === null) {
	        $superadminRole = Role::create([
	            'name' => 'Superadmin',
	            'slug' => 'superadmin',
	            'description' => 'Superadmin Role',
	            'level' => 5,
        	]);
		}

    	if (Role::where('name', '=', 'Admin')->first() === null) {
	        $adminRole = Role::create([
	            'name' => 'Admin',
	            'slug' => 'admin',
	            'description' => 'Admin Role',
	            'level' => 3,
        	]);
	    }

    	if (Role::where('name', '=', 'User')->first() === null) {
	        $userRole = Role::create([
	            'name' => 'User',
	            'slug' => 'user',
	            'description' => 'User Role',
	            'level' => 1,
	        ]);
	    }

    }

}
