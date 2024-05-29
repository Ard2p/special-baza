<?php

namespace App\Machines;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
   protected $fillable = [
       'name', 'type_id'
   ];

   protected $appends = ['edit_url', 'delete_form'];

   function category()
   {
       return $this->belongsTo(Type::class, 'type_id');
   }

   function optional_fields()
   {
       return $this->hasMany(EquipmentOptionalField::class);
   }
   function machine_equipment()
   {
       return $this->hasMany(MachineryEquipment::class);
   }

   function getEditUrlAttribute()
   {
       return route('equipment.edit', $this->id);
   }

    function getDeleteFormAttribute()
    {
        return $this->machine_equipment->isEmpty()
            ? view('admin.machinery.equipment.delete_form', ['id' => $this->id])->render()
            : '';
    }
}
