<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SistasService;
use Illuminate\Http\Request;

class SistasServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin-api');
    }

    public function index()
    {
        $services = SistasService::all();
        return response()->json(['services' => $services]);
    }

    public function create()
    {
        return view('admin.create_sistas_service');
    }


    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|unique:sistas_services',
            'icon' => 'required',
            'description' => 'required',
            'status' => 'required'
        ];
        $customMessages = [
            'title.required' => trans('Title is required'),
            'title.unique' => trans('Title already exist'),
            'icon.required' => trans('Icon is required'),
            'description.required' => trans('Description is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $service = new SistasService();
        $service->title = $request->title;
        $service->icon = $request->icon;
        $service->description = $request->description;
        $service->status = $request->status;
        $service->save();

        $notification = trans('Created Successfully');
        return response()->json(['message' => $notification], 200);
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
            'title.required' => trans('Title is required'),
            'title.unique' => trans('Title already exist'),
            'icon.required' => trans('Icon is required'),
            'description.required' => trans('Description is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $service->title = $request->title;
        $service->icon = $request->icon;
        $service->description = $request->description;
        $service->status = $request->status;
        $service->save();

        $notification = trans('Update Successfully');
        return response()->json(['message' => $notification],200);
    }


    public function destroy($id)
    {
        $service = SistasService::find($id);
        $service->delete();

        $notification = trans('Delete Successfully');
        return response()->json(['message' => $notification],200);
    }

    public function changeStatus($id){
        $service = SistasService::find($id);
        if($service->status == 1){
            $service->status = 0;
            $service->save();
            $message = trans('Inactive Successfully');
        }else{
            $service->status = 1;
            $service->save();
            $message = trans('Active Successfully');
        }
        return response()->json($message);
    }
}
