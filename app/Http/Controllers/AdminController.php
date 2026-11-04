<?php
namespace App\Http\Controllers;
use App\Models\Mission; use App\Models\User; use Illuminate\Http\Request;
class AdminController extends Controller {
 public function index() { return view('admin.index', ['users'=>User::orderBy('role')->orderBy('name')->get(), 'missions'=>Mission::latest('starts_on')->get()]); }
 public function updateUser(Request $request, User $user) { $data=$request->validate(['role'=>'required|in:astronaut,medical_officer,admin','is_active'=>'required|boolean']); $user->update($data); return back()->with('status','Account updated.'); }
 public function updateMissionLink(Request $request, Mission $mission) { $data=$request->validate(['earth_link_status'=>'required|in:online,delayed,blackout','earth_link_delay_minutes'=>'nullable|integer|min:0|max:1440']); $mission->update($data+['earth_link_changed_at'=>now()]); return back()->with('status','Earth-link simulation updated.'); }
}
