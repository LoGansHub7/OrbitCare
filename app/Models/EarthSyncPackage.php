<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EarthSyncPackage extends Model { protected $guarded=[]; protected function casts():array{return ['payload'=>'array','available_at'=>'datetime','sent_at'=>'datetime'];} }
