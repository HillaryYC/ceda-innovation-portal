<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApplicationAttachment extends Model {
 protected $guarded=['id'];
 protected $hidden=['content'];
}
