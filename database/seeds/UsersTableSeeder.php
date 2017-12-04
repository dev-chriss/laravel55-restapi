<?php

use App\User;
use jeremykenedy\LaravelRoles\Models\Role;
use jeremykenedy\LaravelRoles\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userRole 			= Role::where('name', '=', 'User')->first();
    		$adminRole 			= Role::where('name', '=', 'Admin')->first();
    		$superadminRole 	= Role::where('name', '=', 'Superadmin')->first();
    		$permissions 		= Permission::all();

	    /**
	     * Add Users
	     *
	     */
        if (User::where('email', '=', 'superadmin@test.com')->first() === null) {

	        $newUser = User::create([
	            'name' => 'Superadmin',
	            'email' => 'superadmin@test.com',
      				'password' => '123456',
              'status' => 'active'
	        ]);

	        $newUser->attachRole($superadminRole);
    			foreach ($permissions as $permission) {
    				    $newUser->attachPermission($permission);
    			}

          $newUser = User::create([
	            'name' => 'Administrator',
	            'email' => 'admin@test.com',
      				'password' => '123456',
              'status' => 'active'
	        ]);

	        $newUser->attachRole($adminRole);
    			foreach ($permissions as $permission) {
    				    $newUser->attachPermission($permission);
    			}
        }
		/*
        if (User::where('email', '=', 'user@test.com')->first() === null) {

	        $newUser = User::create([
	            'name' => 'User',
	            'email' => 'user@test.com',
	            //'password' => bcrypt('12345'),
				'password' => '12345',
	        ]);

	        $newUser;
	        $newUser->attachRole($userRole);

        }
		*/
    }
}
