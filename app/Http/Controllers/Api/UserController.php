<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Traits\Response;
use App\Mail\SendLoginDetail;
use App\Mail\SendWorkspaceInvication;
use App\User;
use App\UserProject;
use App\UserWorkspace;
use App\Utility;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use Response;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index($slug)
    {
        $currantWorkspace = Utility::getWorkspaceBySlug($slug);
        $users = User::with('currantWorkspace')
            ->select('users.*','user_workspaces.permission')
            ->join('user_workspaces','user_workspaces.user_id', '=', 'users.id')
            ->where('user_workspaces.workspace_id', '=', $currantWorkspace->id)
            ->get()
            ->toArray();

        return $this->sendResponse($users, 'success');
    }

    public function inviteUser($slug, Request $request)
    {
        $currantWorkspace = Utility::getWorkspaceBySlug($slug);
        $post = $request->all();
        $userList = explode(',', $post['users_list']);
        $userList = array_filter($userList);
        foreach ($userList as $email){
            $registerUsers =  User::where('email', $email)->first();
            if(!$registerUsers){
                $arrUser = [];
                $arrUser['name'] = __('No Name');
                $arrUser['email'] = $email;
                $password = Str::random(8);
                $arrUser['password'] = Hash::make($password);
                $arrUser['currant_workspace'] = $currantWorkspace->id;
                $registerUsers = User::create($arrUser);
                $registerUsers->password = $password;

                try {
                    Mail::to($email)->send(new SendLoginDetail($registerUsers));
                }catch (\Exception $e){
                    $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
                }

            }
            // assign workspace first
            $is_assigned = false;
            foreach ($registerUsers->workspace as $workspace){
                if($workspace->id == $currantWorkspace->id){
                    $is_assigned = true;
                }
            }

            if(!$is_assigned){
                UserWorkspace::create(['user_id'=>$registerUsers->id,'workspace_id'=>$currantWorkspace->id,'permission'=>'Member']);

                try {
                    Mail::to($registerUsers->email)->send(new SendWorkspaceInvication($registerUsers, $currantWorkspace));
                }catch (\Exception $e){
                    $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
                }

            }
        }

        return isset($smtp_error) ? $this->sendError($smtp_error) : $this->sendResponse($request->all()->toArray(), 'Users Invited Successfully');
    }

    public function update($slug = null, $id = null, Request $request)
    {
        if($id){
            $objUser = User::find($id);
        }else{
            $objUser = Auth::user();
        }

        $validation = [];
        $validation['name']='required';
        if($request->avatar){
            $validation['avatar']='required|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        }
        $request->validate($validation);

        $post = $request->all();
        if($request->avatar){
            $avatarName = $objUser->id.'_avatar'.time().'.'.$request->avatar->getClientOriginalExtension();
            $request->avatar->storeAs('avatars',$avatarName);
            $post['avatar'] = $avatarName;
        }

        $objUser->update($post);

        return redirect()->back()
            ->with('success',__('User Updated Successfully!'));
    }

    public function removeUser($slug, $id){
        $currantWorkspace = Utility::getWorkspaceBySlug($slug);
        $userWorkspace = UserWorkspace::where('user_id','=',$id)->where('workspace_id','=',$currantWorkspace->id)->first();
        if($userWorkspace) {
            foreach ($currantWorkspace->projects as $project){
                $userProject = UserProject::where('user_id','=',$id)->where('project_id','=',$project->id)->first();
                if($userProject) {
                    $userProject->delete();
                }
            }
            $userWorkspace->delete();
            return $this->sendResponse($currantWorkspace->slug, 'User Removed Successfully!');
        }else{
            return $this->sendError(__('Something is wrong.'));
        }
    }

    public function updatePassword(Request $request)
    {
        if(Auth::guard('api')->Check()) {
            $request->validate([
                'old_password' => 'required',
                'password' => 'required|same:password',
                'password_confirmation' => 'required|same:password',
            ]);
            $objUser = Auth::user();
            $request_data = $request->All();
            $current_password = $objUser->password;

            if(Hash::check($request_data['old_password'], $current_password))
            {
                $user_id = Auth::User()->id;
                $obj_user = User::find($user_id);
                $obj_user->password = Hash::make($request_data['password']);;
                $obj_user->save();
                return $this->sendResponse($objUser->toArray(), 'Password Updated Successfully!');
            }else{
                return $this->sendError('Please Enter Correct Current Password!');
            }
        }
        else{
            return $this->sendError('Some Thing Is Wrong!');
        }
    }
}
