<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\SistasService;
use Exception;
use Illuminate\Http\Request;

class SistasServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $services = SistasService::all();
        return view('admin.sistas_service',compact('services'));
    }

    public function create()
    {
        return view('admin.create_sistas_service');
    }


    public function store(Request $request)
    {       

       try {
        $rules = [
            'title' => 'required|unique:sistas_services',
            'icon' => 'required',
            'description' => 'required',
            'status' => 'required'
        ];
        $customMessages = [
            'title.required' => trans('admin_validation.Title is required'),
            'title.unique' => trans('admin_validation.Title already exist'),
            'icon.required' => trans('admin_validation.Icon is required'),
            'description.required' => trans('admin_validation.Description is required'),
        ];
        $this->validate($request, $rules,$customMessages);
        // dd("Hi");

        $service = new SistasService();
        $service->title = $request->title;
        $service->icon = $request->icon;
        $service->description = $request->description;
        $service->status = $request->status;
        $service->save();

        $notification = trans('admin_validation.Created Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.sistas-service.index')->with($notification);
       } catch (Exception $e) {
        dd($e);
        //throw $th;
       }
    }



    public function show($id)
    {
        $service = SistasService::find($id);
        return response()->json(['service' => $service]);
    }

    public function edit($id)
    {
        $service = SistasService::find($id);
        return view('admin.edit_sistas_service',compact('service'));
    }


    public function update(Request $request, $id)
    {
        $service = SistasService::find($id);
        $rules = [
            'title' => 'required|unique:sistas_services,title,'.$service->id,
            'icon' => 'required',
            'description' => 'required',
            'status' => 'required'
        ];
        $customMessages = [
            'title.required' => trans('admin_validation.Title is required'),
            'title.unique' => trans('admin_validation.Title already exist'),
            'icon.required' => trans('admin_validation.Icon is required'),
            'description.required' => trans('admin_validation.Description is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $service->title = $request->title;
        $service->icon = $request->icon;
        $service->description = $request->description;
        $service->status = $request->status;
        $service->save();

        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.sistas-service.index')->with($notification);
    }


    public function destroy($id)
    {
        $service = SistasService::find($id);
        $service->delete();

        $notification = trans('admin_validation.Delete Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.sistas-service.index')->with($notification);
    }

    public function changeStatus($id){
        $service = SistasService::find($id);
        if($service->status == 1){
            $service->status = 0;
            $service->save();
            $message = trans('admin_validation.Inactive Successfully');
        }else{
            $service->status = 1;
            $service->save();
            $message = trans('admin_validation.Active Successfully');
        }
        return response()->json($message);
    }
}
