<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use App\Product;
use DB;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if(\App\Support\StaffAccess::canManageSite() || ($role && $role->hasPermissionTo('category'))) {
            $lims_categories = Category::where('is_active', true)->pluck('name', 'id');
            $lims_category_all = Category::where('is_active', true)->get();
            return view('category.create',compact('lims_categories', 'lims_category_all'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function categoryData(Request $request)
    {
        $columns = array(
            0 =>'id',
            2 =>'name',
            3=> 'parent_id',
            4=> 'is_active',
        );

        $totalData = Category::where('is_active', true)->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = max(1, (int) $request->input('length'));
        else
            $limit = max(1, (int) $totalData);
        $start = (int) $request->input('start');
        $orderCol = $request->input('order.0.column');
        $order = isset($columns[$orderCol]) ? $columns[$orderCol] : 'id';
        $dir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';
        if(empty($request->input('search.value')))
            $categories = Category::offset($start)
                        ->where('is_active', true)
                        ->limit($limit)
                        ->orderBy($order,$dir)
                        ->get();
        else
        {
            $search = $request->input('search.value');
            $categories =  Category::where([
                            ['name', 'LIKE', "%{$search}%"],
                            ['is_active', true]
                        ])->offset($start)
                        ->limit($limit)
                        ->orderBy($order,$dir)->get();

            $totalFiltered = Category::where([
                            ['name','LIKE',"%{$search}%"],
                            ['is_active', true]
                        ])->count();
        }
        $data = array();
        if(!empty($categories))
        {
            foreach ($categories as $key=>$category)
            {
                $nestedData['id'] = $category->id;
                $nestedData['key'] = $key;

                if($category->image)
                    $nestedData['image'] = '<img src="'.e(\App\Support\PosProductImage::catalogUrl('category', $category->image)).'" height="70" width="70">';
                else
                    $nestedData['image'] = '<img src="'.e(\App\Support\PosProductImage::url('zummXD2dvAtI.png')).'" height="80" width="80">';

                $nestedData['name'] = $category->name;

                if($category->parent_id)
                    $nestedData['parent_id'] = Category::find($category->parent_id)->name;
                else
                    $nestedData['parent_id'] = "N/A";

                $nestedData['number_of_product'] = $category->product()->where('is_active', true)->count();
                $nestedData['stock_qty'] = $category->product()->where('is_active', true)->sum('qty');
                $total_price = $category->product()->where('is_active', true)->sum(DB::raw('price * qty'));
                $total_cost = $category->product()->where('is_active', true)->sum(DB::raw('cost * qty'));

                if(config('currency_position') == 'prefix')
                    $nestedData['stock_worth'] = config('currency').' '.$total_price.' / '.config('currency').' '.$total_cost;
                else
                    $nestedData['stock_worth'] = $total_price.' '.config('currency').' / '.$total_cost.' '.config('currency');

                $nestedData['options'] = $this->categoryActionHtml($category);
                $data[] = $nestedData;
            }
        }
        $json_data = array(
                    "draw"            => intval($request->input('draw')),
                    "recordsTotal"    => intval($totalData),
                    "recordsFiltered" => intval($totalFiltered),
                    "data"            => $data
                    );

        echo json_encode($json_data);
    }

    public function store(Request $request)
    {
        $request->name = preg_replace('/\s+/', ' ', $request->name);
        $this->validate($request, [
            'name' => [
                'max:255',
                    Rule::unique('categories')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif',
        ]);
        $image = $request->image;
        if ($image) {
            $stored = \App\Support\PosProductImage::storeUpload($image, 'category');
            if ($stored) {
                $lims_category_data['image'] = $stored;
            }
        }
        $lims_category_data['name'] = $request->name;
        $lims_category_data['parent_id'] = $request->parent_id;
        $lims_category_data['is_active'] = true;
        if (\App\Support\ServiceCatalog::enabled()) {
            $lims_category_data['service'] = \App\Support\ServiceCatalog::exists($request->service) ? $request->service : null;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'department')) {
            $lims_category_data['department'] = \App\Services\Restaurant\RestaurantDepartment::normalize($request->department);
        }
        Category::create($lims_category_data);
        return redirect('category')->with('message', 'Category inserted successfully');
    }

    public function edit($id)
    {
        $lims_category_data = Category::findOrFail($id);
        $lims_parent_data = Category::where('id', $lims_category_data['parent_id'])->first();
        if($lims_parent_data)
            $lims_category_data['parent'] = $lims_parent_data['name'];
        return $lims_category_data;
    }

    public function update(Request $request)
    {
        $this->validate($request,[
            'name' => [
                'max:255',
                Rule::unique('categories')->ignore($request->category_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif',
        ]);

        $input = $request->except('image');
        if (\App\Support\ServiceCatalog::enabled()) {
            $input['service'] = \App\Support\ServiceCatalog::exists($request->service) ? $request->service : null;
        } else {
            unset($input['service']);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'department')) {
            $input['department'] = \App\Services\Restaurant\RestaurantDepartment::normalize($request->department);
        } else {
            unset($input['department']);
        }
        $image = $request->image;
        if ($image) {
            $stored = \App\Support\PosProductImage::storeUpload($image, 'category');
            if ($stored) {
                $input['image'] = $stored;
            }
        }
        $lims_category_data = Category::findOrFail($request->category_id);
        $lims_category_data->update($input);
        return redirect('category')->with('message', 'Category updated successfully');
    }

    public function import(Request $request)
    {
        //get file
        $upload=$request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        if($ext != 'csv')
            return redirect()->back()->with('not_permitted', 'Please upload a CSV file');
        $filename =  $upload->getClientOriginalName();
        $filePath=$upload->getRealPath();
        //open and read
        $file=fopen($filePath, 'r');
        $header= fgetcsv($file);
        $escapedHeader=[];
        //validate
        foreach ($header as $key => $value) {
            $lheader=strtolower($value);
            $escapedItem=preg_replace('/[^a-z]/', '', $lheader);
            array_push($escapedHeader, $escapedItem);
        }
        //looping through othe columns
        while($columns=fgetcsv($file))
        {
            if($columns[0]=="")
                continue;
            foreach ($columns as $key => $value) {
                $value=preg_replace('/\D/','',$value);
            }
            $data= array_combine($escapedHeader, $columns);
            $category = Category::firstOrNew(['name' => $data['name'], 'is_active' => true ]);
            if($data['parentcategory']){
                $parent_category = Category::firstOrNew(['name' => $data['parentcategory'], 'is_active' => true ]);
                $parent_id = $parent_category->id;
            }
            else
                $parent_id = null;

            $category->parent_id = $parent_id;
            $category->is_active = true;
            $category->save();
        }
        return redirect('category')->with('message', 'Category imported successfully');
    }

    public function deleteBySelection(Request $request)
    {
        $category_id = $request['categoryIdArray'];
        foreach ($category_id as $id) {
            $lims_product_data = Product::where('category_id', $id)->get();
            foreach ($lims_product_data as $product_data) {
                $product_data->is_active = false;
                $product_data->save();
            }
            $lims_category_data = Category::findOrFail($id);
            if($lims_category_data->image)
                unlink('public/images/category/'.$lims_category_data->image);
            $lims_category_data->is_active = false;
            $lims_category_data->save();
        }
        return 'Category deleted successfully!';
    }

    protected function canEditCategories()
    {
        if (\App\Support\StaffAccess::canManageSite()) {
            return true;
        }
        $role = Role::find(optional(auth()->user())->role_id);
        try {
            return $role && $role->hasPermissionTo('category');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function categoryActionHtml(Category $category)
    {
        $edit = trans('file.edit') ?: 'Edit';
        $delete = trans('file.delete') ?: 'Delete';
        $html = '<div class="btn-group btn-group-sm category-actions">'
            . '<button type="button" data-id="'.$category->id.'" class="open-EditCategoryDialog btn btn-default" data-toggle="modal" data-target="#editModal">'
            . '<i class="dripicons-document-edit"></i> '.e($edit)
            . '</button>';
        if ($this->canEditCategories()) {
            $html .= \Form::open(['route' => ['category.destroy', $category->id], 'method' => 'DELETE', 'style' => 'display:inline'])
                . '<button type="submit" class="btn btn-default" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.e($delete).'</button>'
                . \Form::close();
        }
        $html .= '</div>';

        return $html;
    }

    public function destroy($id)
    {
        $lims_category_data = Category::findOrFail($id);
        $lims_category_data->is_active = false;
        $lims_product_data = Product::where('category_id', $id)->get();
        foreach ($lims_product_data as $product_data) {
            $product_data->is_active = false;
            $product_data->save();
        }
        if($lims_category_data->image)
            unlink('public/images/category/'.$lims_category_data->image);
        $lims_category_data->save();
        return redirect('category')->with('not_permitted', 'Category deleted successfully');
    }
}
