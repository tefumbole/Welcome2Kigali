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
            0 => 'id',
            1 => 'has_image',
            2 => 'name',
            3 => 'parent_id',
            4 => 'is_active',
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
            $categories = $this->orderCategoryList(Category::offset($start)
                        ->where('is_active', true)
                        ->limit($limit), $order, $dir)
                        ->get();
        else
        {
            $search = $request->input('search.value');
            $categories = $this->orderCategoryList(Category::where([
                            ['name', 'LIKE', "%{$search}%"],
                            ['is_active', true]
                        ])->offset($start)
                        ->limit($limit), $order, $dir)->get();

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

                $imageUrl = $category->image
                    ? \App\Support\PosProductImage::catalogUrl('category', $category->image)
                    : \App\Support\PosProductImage::url('zummXD2dvAtI.png');
                if ($this->canEditCategories()) {
                    $nestedData['image'] = '<div class="w2k-paste" tabindex="0" data-paste-url="'.e(url('category/'.$category->id.'/paste-image')).'" title="Click, then paste an image"><img src="'.e($imageUrl).'" height="70" width="70"></div>';
                } else {
                    $nestedData['image'] = '<img src="'.e($imageUrl).'" height="70" width="70">';
                }

                $nestedData['name'] = '<a href="'.e(url('products?category_id='.$category->id)).'">'.e($category->name).'</a>';

                if($category->parent_id)
                    $nestedData['parent_id'] = Category::find($category->parent_id)->name;
                else
                    $nestedData['parent_id'] = "N/A";

                $productIds = $this->categoryTreeIds($category->id);
                $categoryProducts = Product::where('is_active', true)->whereIn('category_id', $productIds);
                $nestedData['number_of_product'] = (clone $categoryProducts)->count();
                $nestedData['stock_qty'] = (clone $categoryProducts)->sum('qty');
                $total_price = (clone $categoryProducts)->sum(DB::raw('price * qty'));
                $total_cost = (clone $categoryProducts)->sum(DB::raw('cost * qty'));

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

    public function pasteImage(Request $request, $id)
    {
        if (! $this->canEditCategories()) {
            return response()->json(['message' => 'You are not allowed to edit categories.'], 403);
        }
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp',
        ]);
        $category = Category::where('is_active', true)->findOrFail($id);
        $stored = \App\Support\PosProductImage::storeUpload($request->file('image'), 'category');
        if (! $stored) {
            return response()->json(['message' => 'Could not save that image.'], 422);
        }
        $category->image = $stored;
        $category->save();

        return response()->json([
            'url' => \App\Support\PosProductImage::catalogUrl('category', $stored).'?v='.time(),
        ]);
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

    protected function orderCategoryList($query, $column, $dir)
    {
        $dir = strtolower((string) $dir) === 'desc' ? 'desc' : 'asc';
        if ($column === 'has_image') {
            return $query->orderByRaw("(CASE WHEN image IS NULL OR TRIM(image) = '' OR LOWER(image) = 'null' OR image LIKE 'zummXD2dvAtI.png%' THEN 1 ELSE 0 END) ".$dir)
                ->orderBy('name', 'asc');
        }
        $allowed = ['id', 'name', 'parent_id', 'is_active'];
        if (! in_array($column, $allowed, true)) {
            $column = 'name';
        }

        return $query->orderBy($column, $dir);
    }

    protected function categoryTreeIds($id)
    {
        $id = (int) $id;
        if ($id < 1) {
            return [];
        }
        $children = [];
        foreach (Category::where('is_active', true)->get(['id', 'parent_id']) as $row) {
            $children[(int) $row->parent_id][] = (int) $row->id;
        }
        $ids = [];
        $stack = [$id];
        while ($stack) {
            $current = array_pop($stack);
            if (isset($ids[$current])) {
                continue;
            }
            $ids[$current] = true;
            foreach ($children[$current] ?? [] as $child) {
                $stack[] = $child;
            }
        }

        return array_keys($ids);
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
