<?php

namespace App\Http\Controllers\WEB\Admin;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\ChildCategory;
use App\Models\ProductGallery;
use App\Models\Brand;
use App\Models\ProductSpecificationKey;
use App\Models\ProductSpecification;
use App\Models\OrderProduct;
use App\Models\ProductVariant;
use App\Models\ProductVariantItem;
use App\Models\OrderProductVariant;
use App\Models\ProductReport;
use App\Models\ProductReview;
use App\Models\Wishlist;
use App\Models\Setting;
use App\Models\FlashSaleProduct;
use App\Models\ShoppingCart;
use App\Models\ShoppingCartVariant;
use App\Models\CompareProduct;
use Image;
use File;
use Str;

use App\Exports\ProductExport;
use App\Imports\ProductImport;
use App\Models\Service;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {

        $services = Service::with('category','seller','brand')->where(['vendor_id' => 0])->orderBy('id','desc')->get();
        // Need to give orderService id not admin can able to delete the ordered services
        // $orderService = OrderService::all();
        $setting = Setting::first();
        $frontend_url = $setting->frontend_url;
        $frontend_url = $frontend_url.'/single-product?slug=';
        return view('admin.service',compact('services','setting','frontend_url'));
    }

    public function sellerProduct(){

        $products = Service::with('category','seller','brand')->where('vendor_id','!=',0)->where('approve_by_admin',1)->orderBy('id','desc')->get();
        $orderProducts = OrderProduct::all();
        $setting = Setting::first();
        $frontend_url = $setting->frontend_url;
        $frontend_url = $frontend_url.'/single-product?slug=';
        return view('admin.product',compact('products','orderProducts','setting','frontend_url'));
    }

    public function sellerPendingProduct(){
        $products = Service::with('category','seller','brand')->where('vendor_id','!=',0)->where('approve_by_admin',0)->orderBy('id','desc')->get();
        $orderProducts = OrderProduct::all();
        $setting = Setting::first();
        $frontend_url = $setting->frontend_url;
        $frontend_url = $frontend_url.'/single-product?slug=';

        return view('admin.pending_product',compact('products','orderProducts','setting','frontend_url'));

    }

    public function stockoutProduct(){
        $products = Service::with('category','seller','brand')->where('vendor_id',0)->where('qty',0)->get();
        $orderProducts = OrderProduct::all();
        $setting = Setting::first();
        $frontend_url = $setting->frontend_url;
        $frontend_url = $frontend_url.'/single-product?slug=';

        return view('admin.stockout_product',compact('products','orderProducts','setting','frontend_url'));

    }



    public function create()
    {
        $categories = Category::all();
        $brands = Brand::all();
        $specificationKeys = ProductSpecificationKey::all();

        return view('admin.create_service',compact('categories','brands','specificationKeys'));
    }

    public function store(Request $request)
    {       

      try {
        $rules = [
            'short_name' => 'required',
            'name' => 'required',
            'slug' => 'required|unique:services',
            'thumb_image' => 'required',
            'category' => 'required',
            'short_description' => 'required',
            'long_description' => 'required',
            'price' => 'required|numeric',
            'status' => 'required',
            'weight' => 'required|numeric',
            'quantity' => 'required|numeric',
        ];
        $customMessages = [
            'short_name.required' => trans('admin_validation.Short name is required'),
            'short_name.unique' => trans('admin_validation.Short name is required'),
            'name.required' => trans('admin_validation.Name is required'),
            'name.unique' => trans('admin_validation.Name is required'),
            'slug.required' => trans('admin_validation.Slug is required'),
            'slug.unique' => trans('admin_validation.Slug already exist'),
            'category.required' => trans('admin_validation.Category is required'),
            'thumb_image.required' => trans('admin_validation.thumbnail is required'),
            'short_description.required' => trans('admin_validation.Short description is required'),
            'long_description.required' => trans('admin_validation.Long description is required'),
            'price.required' => trans('admin_validation.Price is required'),
            'status.required' => trans('admin_validation.Status is required'),
            'quantity.required' => trans('admin_validation.Quantity is required'),
            'weight.required' => trans('admin_validation.Weight is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $service = new Service();

        if($request->thumb_image){
            $extention = $request->thumb_image->getClientOriginalExtension();
            $image_name = Str::slug($request->name).date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->thumb_image)
                ->save(public_path().'/'.$image_name);
            $service->thumb_image=$image_name;
        }


        $service->short_name = $request->short_name;
        $service->name = $request->name;
        $service->slug = $request->slug;
        $service->category_id = $request->category;
        $service->sub_category_id = $request->sub_category ? $request->sub_category : 0;
        $service->child_category_id = $request->child_category ? $request->child_category : 0;
        $service->brand_id = $request->brand ? $request->brand : 0;
        $service->sku = $request->sku;
        $service->price = $request->price;
        $service->offer_price = $request->offer_price;
        $service->qty = $request->quantity ? $request->quantity : 0;
        $service->short_description = $request->short_description;
        $service->long_description = $request->long_description;
        $service->status = $request->status;
        $service->weight = $request->weight;
        $service->is_undefine = 1;
        $service->is_specification = $request->is_specification ? 1 : 0;
        $service->seo_title = $request->seo_title ? $request->seo_title : $request->name;
        $service->seo_description = $request->seo_description ? $request->seo_description : $request->name;
        $service->is_top = $request->top_product ? 1 : 0;
        $service->new_service = $request->new_arrival ? 1 : 0;
        $service->is_best = $request->best_product ? 1 : 0;
        $service->is_featured = $request->is_featured ? 1 : 0;
        $service->approve_by_admin = 1;
        $service->save();

        // if($request->is_specification){
        //     $exist_specifications=[];
        //     if($request->keys){
        //         foreach($request->keys as $index => $key){
        //             if($key){
        //                 if($request->specifications[$index]){
        //                     if(!in_array($key, $exist_specifications)){
        //                         $productSpecification= new ProductSpecification();
        //                         $productSpecification->product_id = $product->id;
        //                         $productSpecification->product_specification_key_id = $key;
        //                         $productSpecification->specification = $request->specifications[$index];
        //                         $productSpecification->save();
        //                     }
        //                     $exist_specifications[] = $key;
        //                 }
        //             }
        //         }
        //     }
        // }

        $notification = trans('admin_validation.Created Successfully');

        $notification=array('messege'=>$notification,'alert-type'=>'success');

        return redirect()->route('admin.service.index')->with($notification);
      } catch (Exception $e) {
        dd($e);
        //throw $th;
      }
    }

    public function show($id)
    {
        // 'specifications','reviews','variants','variantItems'
        $product = Service::with('category','brand','gallery')->find($id);
        if($product->vendor_id == 0){
            $notification = 'Something went wrong';
            return response()->json(['error'=>$notification],403);
        }

        return response()->json(['product' => $product], 200);
    }


    public function edit($id)
    {
        $service = Service::with('category','brand','gallery',)->find($id);
        $categories = Category::all();
        $subCategories = SubCategory::where('category_id',$service->category_id)->get();
        $childCategories = ChildCategory::where('sub_category_id', $service->sub_category_id)->get();
        $brands = Brand::all();
        // $specificationKeys = ProductSpecificationKey::all();
        // $productSpecifications = ProductSpecification::where('product_id',$service->id)->get();
        // 'variants','variantItems'
        return view('admin.edit_service',compact('categories','brands','service','subCategories','childCategories',));

    }


    public function update(Request $request, $id)
    {

        $service = Service::find($id);
        $rules = [
            'short_name' => 'required',
            'name' => 'required',
            'slug' => 'required|unique:services,slug,'.$service->id,
            'category' => 'required',
            'short_description' => 'required',
            'long_description' => 'required',
            'price' => 'required|numeric',
            'status' => 'required',
            'weight' => 'required',
        ];
        $customMessages = [
            'short_name.required' => trans('admin_validation.Short name is required'),
            'short_name.unique' => trans('admin_validation.Short name is required'),
            'name.required' => trans('admin_validation.Name is required'),
            'name.unique' => trans('admin_validation.Name is required'),
            'slug.required' => trans('admin_validation.Slug is required'),
            'slug.unique' => trans('admin_validation.Slug already exist'),
            'category.required' => trans('admin_validation.Category is required'),
            'thumb_image.required' => trans('admin_validation.thumbnail is required'),
            'banner_image.required' => trans('admin_validation.Banner is required'),
            'short_description.required' => trans('admin_validation.Short description is required'),
            'long_description.required' => trans('admin_validation.Long description is required'),
            'brand.required' => trans('admin_validation.Brand is required'),
            'price.required' => trans('admin_validation.Price is required'),
            'quantity.required' => trans('admin_validation.Quantity is required'),
            'status.required' => trans('admin_validation.Status is required'),
            'weight.required' => trans('admin_validation.Weight is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        if($request->thumb_image){
            $old_thumbnail = $service->thumb_image;
            $extention = $request->thumb_image->getClientOriginalExtension();
            $image_name = Str::slug($request->name).date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->thumb_image)
                ->save(public_path().'/'.$image_name);
            $service->thumb_image=$image_name;
            $service->save();
            if($old_thumbnail){
                if(File::exists(public_path().'/'.$old_thumbnail))unlink(public_path().'/'.$old_thumbnail);
            }
        }


        $service->short_name = $request->short_name;
        $service->name = $request->name;
        $service->slug = $request->slug;
        $service->category_id = $request->category;
        $service->sub_category_id = $request->sub_category ? $request->sub_category : 0;
        $service->child_category_id = $request->child_category ? $request->child_category : 0;
        $service->brand_id = $request->brand ? $request->brand : 0;
        $service->sku = $request->sku;
        $service->price = $request->price;
        $service->offer_price = $request->offer_price;
        $service->short_description = $request->short_description;
        $service->long_description = $request->long_description;
        $service->tags = $request->tags;
        $service->status = $request->status;
        $service->weight = $request->weight;
        $service->is_specification = $request->is_specification ? 1 : 0;
        $service->seo_title = $request->seo_title ? $request->seo_title : $request->name;
        $service->seo_description = $request->seo_description ? $request->seo_description : $request->name;
        $service->is_top = $request->top_product ? 1 : 0;
        $service->new_service = $request->new_arrival ? 1 : 0;
        $service->is_best = $request->best_product ? 1 : 0;
        $service->is_featured = $request->is_featured ? 1 : 0;
        if($service->vendor_id != 0){
            $service->approve_by_admin = $request->approve_by_admin;
        }
        $service->save();

        // $exist_specifications=[];
        // if($request->keys){
        //     foreach($request->keys as $index => $key){
        //         if($key){
        //             if($request->specifications[$index]){
        //                 if(!in_array($key, $exist_specifications)){
        //                     $existSroductSpecification = ProductSpecification::where(['product_id' => $service->id,'product_specification_key_id' => $key])->first();
        //                     if($existSroductSpecification){
        //                         $existSroductSpecification->specification = $request->specifications[$index];
        //                         $existSroductSpecification->save();
        //                     }else{
        //                         $productSpecification = new ProductSpecification();
        //                         $productSpecification->product_id = $service->id;
        //                         $productSpecification->product_specification_key_id = $key;
        //                         $productSpecification->specification = $request->specifications[$index];
        //                         $productSpecification->save();
        //                     }
        //                 }
        //                 $exist_specifications[] = $key;
        //             }
        //         }
        //     }
        // }
        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.service.index')->with($notification);
    }

    public function destroy($id)
    {
        $product = Service::find($id);
        $gallery = $product->gallery;
        $old_thumbnail = $product->thumb_image;
        $product->delete();
        if($old_thumbnail){
            if(File::exists(public_path().'/'.$old_thumbnail))unlink(public_path().'/'.$old_thumbnail);
        }
        foreach($gallery as $image){
            $old_image = $image->image;
            $image->delete();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }
        ProductVariant::where('product_id',$id)->delete();
        ProductVariantItem::where('product_id',$id)->delete();
        FlashSaleProduct::where('product_id',$id)->delete();
        ProductReport::where('product_id',$id)->delete();
        ProductReview::where('product_id',$id)->delete();
        ProductSpecification::where('product_id',$id)->delete();
        Wishlist::where('product_id',$id)->delete();
        $cartProducts = ShoppingCart::where('product_id',$id)->get();
        foreach($cartProducts as $cartProduct){
            ShoppingCartVariant::where('shopping_cart_id', $cartProduct->id)->delete();
            $cartProduct->delete();
        }
        CompareProduct::where('product_id',$id)->delete();

        $notification = trans('admin_validation.Delete Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.service.index')->with($notification);
    }

    public function changeStatus($id){
        $product = Service::find($id);
        if($product->status == 1){
            $product->status = 0;
            $product->save();
            $message = trans('admin_validation.InActive Successfully');
        }else{
            $product->status = 1;
            $product->save();
            $message = trans('admin_validation.Active Successfully');
        }
        return response()->json($message);
    }

    public function productApproved($id){
        $product = Service::find($id);
        if($product->approve_by_admin == 1){
            $product->approve_by_admin = 0;
            $product->save();
            $message = trans('admin_validation.Reject Successfully');
        }else{
            $product->approve_by_admin = 1;
            $product->save();
            $message = trans('admin_validation.Approved Successfully');
        }
        return response()->json($message);
    }



    public function removedProductExistSpecification($id){
        $productSpecification = ProductSpecification::find($id);
        $productSpecification->delete();
        $message = trans('admin_validation.Removed Successfully');
        return response()->json($message);
    }




    public function product_import_page()
    {
        return view('admin.product_import_page');
    }

    public function product_export()
    {
        $is_dummy = false;
        return Excel::download(new ProductExport($is_dummy), 'products.xlsx');
    }


    public function demo_product_export()
    {
        $is_dummy = true;
        return Excel::download(new ProductExport($is_dummy), 'products.xlsx');
    }



    public function product_import(Request $request)
    {
        try{
            Excel::import(new ProductImport, $request->file('import_file'));

            $notification=trans('Uploaded Successfully');
            $notification=array('messege'=>$notification,'alert-type'=>'success');
            return redirect()->back()->with($notification);

        }catch(Exception $ex){
            $notification=trans('Please follow the instruction and input the value carefully');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }


    }

}
