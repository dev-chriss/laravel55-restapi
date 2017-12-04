<?php

use App\User;
use jeremykenedy\LaravelRoles\Models\Role;
use jeremykenedy\LaravelRoles\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class ConnectRelationshipsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

	    /**
	     * Get Available Permissions
	     *
	     */
		$permissions = Permission::all();

	    /**
	     * Attach Permissions to Roles
	     *
	     */
		$roleSuperadmin = Role::where('name', '=', 'Superadmin')->first();
		foreach ($permissions as $permission) {
			$roleSuperadmin->attachPermission($permission);
		}

    }

}