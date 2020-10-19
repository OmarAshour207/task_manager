<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Traits\Response;
use App\Mail\SendInvication;
use App\Mail\SendLoginDetail;
use App\Mail\SendWorkspaceInvication;
use App\Project;
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

class ProjectController extends Controller
{
    use Response;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index($slug)
    {
        $objUser = Auth::guard('api')->user();
        $currantWorkspace = Utility::getWorkspaceBySlug($slug);
        $projects = Project::select('projects.*')
            ->join('user_projects','projects.id','=','user_projects.project_id')
            ->where('user_projects.user_id','=',$objUser->id)
            ->where('projects.workspace','=',$currantWorkspace->id)
            ->get();
        return $this->sendResponse($projects->toArray(), 'success');
    }

    public function store($slug, Request $request)
    {
        $currantWorkspace = Utility::getWorkspaceBySlug($slug);
        $request->validate([
            'name' => 'required',
        ]);

        $objUser = Auth::guard('api')->user();

        $post = $request->all();

        $post['workspace'] = $currantWorkspace->id;
        $post['created_by'] = $objUser->id;
        $userList = [];
        if(isset($post['users_list'])) {
            $userList = $post['users_list'];
        }
        $userList[] = $objUser->email;
        $userList = array_filter($userList);
        $objProject = Project::create($post);

        foreach ($userList as $email){
            $permission = 'Member';
            $registerUsers =  User::where('email',$email)->first();
            if($registerUsers){
                if($registerUsers->id == $objUser->id){
                    $permission = 'Owner';
                }
                $this->inviteUser($registerUsers,$objProject,$permission);
            }
            else{
                $arrUser = [];
                $arrUser['name'] = 'No Name';
                $arrUser['email'] = $email;
                $password = Str::random(8);
                $arrUser['password'] = Hash::make($password);
                $arrUser['currant_workspace'] = $objProject->workspace;
                $registerUsers = User::create($arrUser);
                $registerUsers->password = $password;

                try {
                    Mail::to($email)->send(new SendLoginDetail($registerUsers));
                }catch (\Exception $e){
                    $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
                }

                $this->inviteUser($registerUsers,$objProject,$permission);
            }
        }

        return $this->sendResponse($currantWorkspace->slug, 'Project Created Successfully!');
    }

    public function inviteUser(User $user,Project $project,$permission){

        // assign workspace first
        $is_assigned = false;
        foreach ($user->workspace as $workspace){
            if($workspace->id == $project->workspace){
                $is_assigned = true;
            }
        }

        if(!$is_assigned){
            UserWorkspace::create(['user_id'=>$user->id,'workspace_id'=>$project->workspace,'permission'=>$permission]);
            try {
                Mail::to($user->email)->send(new SendWorkspaceInvication($user, $project->workspaceData));
            }catch (\Exception $e){
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }
        }

        // assign project
        $arrData = [];
        $arrData['user_id'] = $user->id;
        $arrData['project_id'] = $project->id;
        $is_invited = UserProject::where($arrData)->first();
        if(!$is_invited) {
            UserProject::create($arrData);
            if ($permission != 'Owner'){
                try {
                    Mail::to($user->email)->send(new SendInvication($user, $project));
                }catch (\Exception $e){
                    $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
                }
            }
        }
    }

    public function show($slug, $projectID)
    {
        $objUser = Auth::guard('api')->user();
        $currantWorkspace = Utility::getWorkspaceBySlug($slug);
        $project = Project::select('projects.*')
                ->with('workspaceData')
                ->with('users')
                ->join('user_projects','projects.id', '=', 'user_projects.project_id')
                ->where('user_projects.user_id','=',$objUser->id)
                ->where('projects.workspace','=',$currantWorkspace->id)
                ->where('projects.id','=',$projectID)
                ->first();
        $project->countTask = $project->countTask();
        $project->countTaskComments = $project->countTaskComments();

        $datetime1 = new \DateTime($project->end_date);
        $datetime2 = new \DateTime(date('Y-m-d'));
        $interval = $datetime1->diff($datetime2);
        $days = $interval->format('%a');

        $project->daysLeft = $days;
        $project->milestones = $project->milestones();
        $project->files = $project->files();
        $project->activities = $project->activities();

        return $this->sendResponse($project->toArray(), 'projects');
    }

    public function update(Request $request, $slug, $projectID)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $objUser = Auth::guard('api')->user();
        $currantWorkspace = Utility::getWorkspaceBySlug($slug);
        $project = Project::select('projects.*')->join('user_projects','projects.id','=','user_projects.project_id')->where('user_projects.user_id','=',$objUser->id)->where('projects.workspace','=',$currantWorkspace->id)->where('projects.id','=',$projectID)->first();
        $project->update($request->all());

        return $this->sendResponse($project, 'Project Updated Successfully!');
    }

    public function destroy($slug, $projectID)
    {
        $objUser = Auth::guard('api')->user();
        $project = Project::find($projectID);

        if($project->created_by == $objUser->id) {
            UserProject::where('project_id', '=', $projectID)->delete();
            $project->delete();
            return $this->sendResponse($slug, __('Project Deleted Successfully!'));
        }
        else{
            return $this->sendError(__('You can\'t Delete Project!'));
        }
    }

}
