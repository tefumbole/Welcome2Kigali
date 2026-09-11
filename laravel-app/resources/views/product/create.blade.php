@extends('layout.main')

@section('content')
<style>
    .w2k-product-page {
        background: linear-gradient(180deg, #f7f1e8 0%, #fffdf8 42%, #f3e6cf 100%);
        border-radius: 20px;
        padding: 8px 8px 28px;
    }
    .w2k-product-hero {
        padding: 22px 24px 8px;
    }
    .w2k-product-hero h4 {
        margin: 0;
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 28px;
        color: #1a1a1a;
        letter-spacing: .01em;
    }
    .w2k-product-hero p {
        margin: 6px 0 0;
        color: #7a6238;
        font-size: 14px;
    }
    .w2k-product-page .card {
        border: 0;
        background: transparent;
        box-shadow: none;
    }
    .w2k-product-page .card-body {
        padding: 8px 16px 16px;
    }
    .w2k-product-page .form-group label,
    .w2k-product-page label {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #5c4630;
    }
    .w2k-product-page .form-control,
    .w2k-product-page .bootstrap-select .dropdown-toggle {
        border: 1px solid #e4d3b0;
        border-radius: 12px !important;
        background: #fffefb;
        min-height: 42px;
        box-shadow: none;
    }
    .w2k-product-page .form-control:focus {
        border-color: #c5a059;
        box-shadow: 0 0 0 3px rgba(197,160,89,.18);
        background: #fff;
    }
    .w2k-pf-section {
        margin: 8px 0 4px;
        padding: 14px 4px 0;
        border-top: 1px solid rgba(197,160,89,.28);
    }
    .w2k-pf-section:first-of-type { border-top: 0; padding-top: 0; }
    .w2k-pf-kicker {
        display: inline-block;
        margin-bottom: 10px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #1a1a1a;
        color: #c5a059;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .w2k-product-page #imageUpload.dropzone {
        border: 2px dashed #c5a059;
        border-radius: 16px;
        background: #fff8ea;
        min-height: 140px;
    }
    .w2k-product-page #submit-btn,
    .w2k-product-save {
        background: #1a1a1a;
        border: 0;
        color: #c5a059;
        border-radius: 999px;
        padding: 12px 28px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .w2k-product-page #submit-btn:hover,
    .w2k-product-save:hover {
        background: #c5a059;
        color: #1a1a1a;
    }
    .w2k-product-page .membership-benefit-box,
    .w2k-product-page #diffPrice-option,
    .w2k-product-page #batch-option,
    .w2k-product-page #variant-option,
    .w2k-product-page .promotion {
        background: #fffdf8;
        border: 1px solid #ead9b4;
        border-radius: 14px;
        padding: 14px 16px;
        margin-top: 12px;
    }
</style>
<section class="forms w2k-product-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="w2k-product-hero">
                        <h4>{{trans('file.add_product')}}</h4>
                        <p>Add a cafe or shop item. Fields with * are required. Image is optional.</p>
                    </div>
                    <div class="card-body">
                        <form id="product-form">
                            <div class="row">
                                <div class="col-12 w2k-pf-section"><span class="w2k-pf-kicker">The item</span></div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Type')}} *</strong> </label>
                                        <div class="input-group">
                                            <select name="type" required class="form-control selectpicker" id="type">
                                                <option value="standard">Standard</option>
                                                <option value="digital">Digital</option>
                                                @if(auth()->user()->role_id == 1)
                                                    @if(!in_array("zero_stock", $all_permission))
                                                        <option value="combo">Combo</option>
                                                    @endif
                                                @endif
                                                @if(auth()->user()->role_id != 12 || auth()->user()->can_donation == 1)
                                                    <option value="donation">Donation</option>
                                                @endif
                                                @if(auth()->user()->role_id != 12 || auth()->user()->can_service == 1)
                                                    <option value="service">Service</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Name')}} *</strong> </label>
                                        <input type="text" name="name" class="form-control" id="name" aria-describedby="name" required>
                                        <span class="validation-msg" id="name-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Code')}} *</strong> </label>
                                        <div class="input-group">
                                            <input type="text" name="code" class="form-control" id="code" aria-describedby="code" required value="{{ $generatedCode ?? '' }}">
                                            <div class="input-group-append">
                                                <button id="genbutton" type="button" class="btn btn-sm btn-default" title="{{trans('file.Generate')}}"><i class="fa fa-refresh"></i></button>
                                            </div>
                                        </div>
                                        <span class="validation-msg" id="code-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4 barcode_symbology">
                                    <div class="form-group">
                                        <label>{{trans('file.Barcode Symbology')}} *</strong> </label>
                                        <div class="input-group">
                                            <select name="barcode_symbology" required class="form-control selectpicker">
                                                <option value="C128">Code 128</option>
                                                <option value="C39">Code 39</option>
                                                <option value="UPCA">UPC-A</option>
                                                <option value="UPCE">UPC-E</option>
                                                <option value="EAN8">EAN-8</option>
                                                <option value="EAN13">EAN-13</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="digital" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Attach File')}}</strong> </label>
                                        <div class="input-group">
                                            <input type="file" name="file" class="form-control">
                                        </div>
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div id="combo" class="col-md-9 mb-1">
                                    <label>{{trans('file.add_product')}}</label>
                                    <div class="search-box input-group mb-3">
                                        <button class="btn btn-secondary"><i class="fa fa-barcode"></i></button>
                                        <input type="text" name="product_code_name" id="lims_productcodeSearch" placeholder="Please type product code and select..." class="form-control" />
                                    </div>
                                    <label>{{trans('file.Combo Products')}}</label>
                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-hover order-list">
                                            <thead>
                                                <tr>
                                                    <th>{{trans('file.product')}}</th>
                                                    <th>{{trans('file.Quantity')}}</th>
                                                    <th>{{trans('file.Unit Price')}}</th>
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-4 brand_id">
                                    <div class="form-group">
                                        <label>{{trans('file.Brand')}}</strong> </label>
                                        <div class="input-group">
                                          <select name="brand_id" class="selectpicker form-control" data-live-search="true"   title="Select Brand...">
                                            @foreach($lims_brand_list as $brand)
                                                <option value="{{$brand->id}}" {{ $brand->title === 'WC2K' ? 'selected' : '' }}>{{$brand->title}}</option>
                                            @endforeach
                                          </select>
                                      </div>
                                    </div>
                                </div>
                                <div class="col-md-4 category_id">
                                    <div class="form-group">
                                        <label>{{trans('file.category')}} *</strong> </label>
                                        <div class="input-group">
                                          <select name="category_id" required class="selectpicker form-control" data-live-search="true"   title="Select Category...">
                                            @foreach($lims_category_list as $category)
                                                <option value="{{$category->id}}" {{ $general_setting->category == $category->id ? 'selected' : ''}}>{{$category->name}}</option>
                                            @endforeach
                                          </select>
                                      </div>
                                      <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div class="col-12 w2k-pf-section"><span class="w2k-pf-kicker">Units &amp; stock</span></div>
                                <div id="unit" class="col-md-12">
                                    <div class="row ">
                                        <div class="col-md-4 form-group">
                                                <label>{{trans('file.Product Unit')}} *</strong> </label>
                                                <div class="input-group">
                                                  <select required class="form-control selectpicker" name="unit_id">
                                                    <option value="" disabled {{ empty($general_setting->unit) ? 'selected' : '' }}>Select Product Unit...</option>
                                                    @foreach($lims_unit_list as $unit)
                                                        @if($unit->base_unit==null)
                                                            <option value="{{$unit->id}}" {{ (int) $general_setting->unit === (int) $unit->id ? 'selected' : '' }}>{{$unit->unit_name}}</option>
                                                        @endif
                                                    @endforeach
                                                  </select>
                                              </div>
                                              <span class="validation-msg"></span>
                                        </div>
                                        <div class="col-md-4">
                                                <label>{{trans('file.Sale Unit')}}</strong> </label>
                                                <div class="input-group">
                                                  <select class="form-control selectpicker" name="sale_unit_id">
                                                  </select>
                                              </div>
                                        </div>
                                        <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{trans('file.Purchase Unit')}}</strong> </label>
                                                    <div class="input-group">
                                                      <select class="form-control selectpicker" name="purchase_unit_id">
                                                      </select>
                                                  </div>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 w2k-pf-section"><span class="w2k-pf-kicker">Price</span></div>
                                <div id="cost" class="col-md-4">
                                     <div class="form-group">
                                        <label>{{trans('file.Product Cost')}} *</strong> </label>
                                        <input type="number" name="cost" required class="form-control" step="any">
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Price')}} *</strong> </label>
                                        <input type="number" name="price" required class="form-control" step="any">
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div class="col-md-4 qty">
                                    <div class="form-group">
                                        <label>{{trans('file.Quantity')}} *</strong> </label>
                                        <input type="number" name="qty" required class="form-control" step="any" value="0">
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                @if($role->hasPermissionTo('booking_module'))
                                    <div id="cost" class="col-md-4 rent_price_per_hour">
                                        <div class="form-group">
                                            <label>Product Rent Price / Hour</label>
                                            <input type="number" name="rent_price_per_hour" class="form-control" step="any" placeholder="Product Rent Price per Hour">
                                            <span class="validation-msg"></span>
                                        </div>
                                    </div>
                                    <div id="cost" class="col-md-4">
                                        <div class="form-group rent_price_per_day">
                                            <label>Product Rent Price / Day</label>
                                            <input type="number" name="rent_price_per_day" class="form-control" step="any" placeholder="Product Rent Price per Day">
                                            <span class="validation-msg"></span>
                                        </div>
                                    </div>
                                    <div id="cost" class="col-md-4 rent_price_per_month">
                                        <div class="form-group">
                                            <label>Product Rent Price / Month</label>
                                            <input type="number" name="rent_price_per_month" class="form-control" step="any" placeholder="Product Rent Price per Month">
                                            <span class="validation-msg"></span>
                                        </div>
                                    </div>
                                @endif
                                <div id="alert-qty" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Alert Quantity')}}</strong> </label>
                                        <input type="number" name="alert_quantity" class="form-control" step="any">
                                    </div>
                                </div>
                                <div class="col-md-4 tax_id">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Tax')}}</strong> </label>
                                        <select name="tax_id" class="form-control selectpicker">
                                            <option value="">No Tax</option>
                                            @foreach($lims_tax_list as $tax)
                                                <option value="{{$tax->id}}">{{$tax->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 tax_method">
                                    <div class="form-group">
                                        <label>{{trans('file.Tax Method')}}</strong> </label> <i class="dripicons-question" data-toggle="tooltip" title="{{trans('file.Exclusive: Poduct price = Actual product price + Tax. Inclusive: Actual product price = Product price - Tax')}}"></i>
                                        <select name="tax_method" class="form-control selectpicker">
                                            <option value="1">{{trans('file.Exclusive')}}</option>
                                            <option value="2">{{trans('file.Inclusive')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 product_location">
                                    <div class="form-group">
                                        <label>Product Location</label>
                                        <input type="text" name="product_location" class="form-control" placeholder="Product location">
                                    </div>
                                </div>
                                <div class="col-md-4 featured">
                                    <div class="form-group mt-3">
                                        <input type="checkbox" name="featured" value="1">&nbsp;
                                        <label>{{trans('file.Featured')}}</label>
                                        <p class="italic">{{trans('file.Featured product will be displayed in POS')}}</p>
                                    </div>
                                </div>
                                <div class="col-12 w2k-pf-section"><span class="w2k-pf-kicker">Photo &amp; notes</span></div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Image')}}</strong> </label> <i class="dripicons-question" data-toggle="tooltip" title="{{trans('file.You can upload multiple image. Only .jpeg, .jpg, .png, .gif file can be uploaded. First image will be base image.')}}"></i>
                                        <p class="text-muted small mb-1">Click or drop images here, or paste with Ctrl+V / ⌘V.</p>
                                        <div id="imageUpload" class="dropzone"></div>
                                        <span class="validation-msg" id="image-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Details')}}</label>
                                        <textarea name="product_details" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="col-12 w2k-pf-section"><span class="w2k-pf-kicker">More options</span></div>
                                <div class="col-md-12 mt-2" id="diffPrice-option">
                                    <h5><input name="is_diffPrice" type="checkbox" id="is-diffPrice" value="1">&nbsp; {{trans('file.This product has different price for different warehouse')}}</h5>
                                </div>
                                <div class="col-md-6" id="diffPrice-section">
                                    <div class="table-responsive ml-2">
                                        <table id="diffPrice-table" class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>{{trans('file.Warehouse')}}</th>
                                                    <th>{{trans('file.Price')}}</th>
                                                </tr>
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="warehouse_id[]" value="{{$warehouse->id}}">
                                                        {{$warehouse->name}}
                                                    </td>
                                                    <td><input type="number" name="diff_price[]" class="form-control"></td>
                                                </tr>
                                                @endforeach
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3" id="batch-option">
                                    <h5><input name="is_batch" type="checkbox" id="is-batch" value="1">&nbsp; {{trans('file.This product has batch and expired date')}}</h5>
                                </div>
                                <div class="col-md-12 mt-3" id="variant-option">
                                    <h5><input name="is_variant" type="checkbox" id="is-variant" value="1">&nbsp; {{trans('file.This product has variant')}}</h5>
                                </div>
                                <div class="col-md-12" id="variant-section">
                                    <div class="col-md-6 form-group mt-2">
                                        <input type="text" name="variant" class="form-control" placeholder="{{trans('file.Enter variant seperated by comma')}}">
                                    </div>
                                    <div class="table-responsive ml-2">
                                        <table id="variant-table" class="table table-hover variant-list">
                                            <thead>
                                                <tr>
                                                    <th><i class="dripicons-view-apps"></i></th>
                                                    <th>{{trans('file.name')}}</th>
                                                    <th>{{trans('file.Item Code')}}</th>
                                                    <th>{{trans('file.Additional Price')}}</th>
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-3 promotion">
                                    <input name="promotion" type="checkbox" id="promotion" value="1">&nbsp;
                                    <label><h5> {{trans('file.Add Promotional Price')}}</h5></label>
                                </div>
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-4" id="promotion_price">
                                            <label>{{trans('file.Promotional Price')}}</label>
                                            <input type="number" name="promotion_price" class="form-control" step="any" />
                                        </div>
                                        <div class="col-md-4" id="start_date">
                                            <div class="form-group">
                                                <label>{{trans('file.Promotion Starts')}}</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text"><i class="dripicons-calendar"></i></div>
                                                    </div>
                                                    <input type="text" name="starting_date" id="starting_date" class="form-control" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4" id="last_date">
                                            <div class="form-group">
                                                <label>{{trans('file.Promotion Ends')}}</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text"><i class="dripicons-calendar"></i></div>
                                                    </div>
                                                    <input type="text" name="last_date" id="ending_date" class="form-control" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3 membership-benefit-box">
                                    <h5>Membership Benefit</h5>
                                    <p class="text-muted small">Optional free or special-price item for Welcome to Kigali members at POS.</p>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label><input type="checkbox" name="membership_benefit" value="1" id="membership_benefit"> Enable for members</label>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Kind</label>
                                            <select name="membership_benefit_kind" class="form-control">
                                                <option value="free">Free</option>
                                                <option value="member_price">Member price</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label>Member price</label>
                                            <input type="number" step="any" name="membership_member_price" class="form-control">
                                        </div>
                                        <div class="col-md-2">
                                            <label>Qty</label>
                                            <input type="number" name="membership_benefit_qty" class="form-control" value="1" min="1">
                                        </div>
                                        <div class="col-md-2">
                                            <label>Frequency</label>
                                            <select name="membership_benefit_frequency" class="form-control">
                                                <option value="unlimited">Unlimited</option>
                                                <option value="once_per_day">Per day</option>
                                                <option value="once_per_week">Per week</option>
                                                <option value="once_per_month">Per month</option>
                                                <option value="once_per_period">Per membership period</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mt-4">
                                <input type="button" value="Save product" id="submit-btn" class="btn w2k-product-save">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">

    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    $("ul#product #product-create-menu").addClass("active");

    $("#digital").hide();
    $("#combo").hide();
    $("#variant-section").hide();
    $("#diffPrice-section").hide();
    $("#promotion_price").hide();
    $("#start_date").hide();
    $("#last_date").hide();

    $('[data-toggle="tooltip"]').tooltip();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function fillGeneratedCode() {
      $.get('{{ url('products/gencode') }}', function(data){
        if (data) {
          $("input[name='code']").val(data);
        }
      });
    }
    $('#genbutton').on("click", function(){
      fillGeneratedCode();
    });
    if (!$.trim($("input[name='code']").val())) {
      fillGeneratedCode();
    }



    tinymce.init({
      selector: 'textarea',
      height: 130,
      plugins: [
        'advlist autolink lists link image charmap print preview anchor textcolor',
        'searchreplace visualblocks code fullscreen',
        'insertdatetime media table contextmenu paste code wordcount'
      ],
      toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
      branding:false
    });

    $('select[name="type"]').on('change', function() {
        $(".rent_price_per_hour").show();
        $(".rent_price_per_day").show();
        $(".rent_price_per_month").show();
        $(".qty").show();
        $("input[name='cost']").val();
        $(".category_id").show();
        $(".barcode_symbology").show();
        $(".brand_id").show();

        $(".tax_id").show();
        $(".tax_method").show();
        $(".product_location").show();
        $(".featured").show();
        $(".promotion").show(300);
        $("#batch-option").show(300);
        $("#digital").show(300);
        $("input[name='file']").show(300);

        if($(this).val() == 'combo'){
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            hide();
            $("#combo").show(300);
            $("input[name='price']").prop('disabled',true);
            $("#is-variant").prop("checked", false);
            $("#is-diffPrice").prop("checked", false);
            $("#variant-section, #variant-option, #diffPrice-option, #diffPrice-section").hide(300);
        }
        else if($(this).val() == 'digital'){
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            // $("input[name='file']").prop('required',true);
            hide();
            $("#digital").show(300);
            $("#combo").hide(300);
            $("input[name='price']").prop('disabled',false);
            $("#is-variant").prop("checked", false);
            $("#is-diffPrice").prop("checked", false);
            $("#variant-section, #variant-option, #diffPrice-option, #diffPrice-section").hide(300);
        }
        else if($(this).val() == 'standard'){
            $("input[name='cost']").prop('required',true);
            $("select[name='unit_id']").prop('required',true);
            $("input[name='file']").prop('required',false);
            $("#cost").show(300);
            $("#unit").show(300);
            $("#alert-qty").show(300);
            $("#variant-option").show(300);
            $("#diffPrice-option").show(300);
            $("#digital").hide(300);
            $("#combo").hide(300);
            $("input[name='price']").prop('disabled',false);
        }
        else if($(this).val() == 'donation') {
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            $("input[name='file']").prop('required',false);
            $("input[name='file']").hide();
            $("#cost").hide(300);
            $("#unit").hide(300);
            $("#alert-qty").hide(300);
            $("#variant-option").hide(300);
            $("#diffPrice-option").hide(300);
            $("#digital").hide(300);
            $("#combo").hide(300);
            $("input[name='price']").prop('disabled',false);
            $(".rent_price_per_hour").hide();
            $(".rent_price_per_day").hide();
            $(".rent_price_per_month").hide();
            $(".qty").hide();
            $("input[name='cost']").val(0);
            $(".category_id").hide();
            $(".barcode_symbology").hide();
            $(".brand_id").hide();

            $(".tax_id").hide();
            $(".tax_method").hide();
            $(".product_location").hide();
            $(".featured").hide();
            $(".promotion").hide();
            $("#batch-option").hide();
        } else if($(this).val() == 'service') {
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            $("input[name='file']").prop('required',false);
            $("input[name='file']").hide();
            $("#cost").hide(300);
            $("#unit").hide(300);
            $("#alert-qty").hide(300);
            $("#variant-option").show(300);
            $("#diffPrice-option").hide(300);
            $("#digital").hide(300);
            $("#combo").hide(300);
            $("input[name='price']").prop('disabled',false);
            $(".rent_price_per_hour").hide();
            $(".rent_price_per_day").hide();
            $(".rent_price_per_month").hide();
            $(".qty").hide();
            $("input[name='cost']").val(0);
            $(".category_id").hide();
            $(".barcode_symbology").hide();
            $(".brand_id").hide();

            $(".tax_id").hide();
            $(".tax_method").hide();
            $(".product_location").hide();
            $(".featured").hide();
            $(".promotion").hide();
            $("#batch-option").hide();
        }
    });

    $('select[name="unit_id"]').on('change', function() {

        unitID = $(this).val();
        if(unitID) {
            populate_category(unitID);
        }else{
            $('select[name="sale_unit_id"]').empty();
            $('select[name="purchase_unit_id"]').empty();
        }
    });
    if ($('select[name="unit_id"]').val()) {
        populate_category($('select[name="unit_id"]').val());
    }
    <?php $productArray = []; ?>
    var lims_product_code = [ @foreach($lims_product_list as $product)
        <?php
            $productArray[] = htmlspecialchars($product->code . ' [ ' . $product->name . ' ]');
        ?>
         @endforeach
            <?php
                echo  '"'.implode('","', $productArray).'"';
            ?> ];

    var lims_productcodeSearch = $('#lims_productcodeSearch');

    lims_productcodeSearch.autocomplete({
        source: function(request, response) {
            var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
            response($.grep(lims_product_code, function(item) {
                return matcher.test(item);
            }));
        },
        select: function(event, ui) {
            var data = ui.item.value;
            $.ajax({
                type: 'GET',
                url: 'search',
                data: {
                    data: data
                },
                success: function(data) {
                    var flag = 1;
                    $(".product-id").each(function() {
                        if ($(this).val() == data[4]) {
                            alert('Duplicate input is not allowed!')
                            flag = 0;
                        }
                    });
                    $("input[name='product_code_name']").val('');
                    if(flag){
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>' + data[0] +' [' + data[1] + ']</td>';
                        cols += '<td><input type="number" class="form-control qty" name="product_qty[]" value="1" step="any"/></td>';
                        cols += '<td><input type="number" class="form-control unit_price" name="unit_price[]" value="' + data[3] + '" step="any"/></td>';
                        cols += '<td><button type="button" class="ibtnDel btn btn-sm btn-danger">X</button></td>';
                        cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data[4] + '"/>';

                        newRow.append(cols);
                        $("table.order-list tbody").append(newRow);
                        calculate_price();
                    }
                }
            });
        }
    });

    //Change quantity or unit price
    $("#myTable").on('input', '.qty , .unit_price', function() {
        calculate_price();
    });

    //Delete product
    $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
        $(this).closest("tr").remove();
        calculate_price();
    });

    function hide() {
        $("#cost").hide(300);
        $("#unit").hide(300);
        $("#alert-qty").hide(300);
    }

    function calculate_price() {
        var price = 0;
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            quantity =  $(this).val();
            unit_price = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .unit_price').val();
            price += quantity * unit_price;
        });
        $('input[name="price"]').val(price);
    }

    function populate_category(unitID){
        $.ajax({
            url: 'saleunit/'+unitID,
            type: "GET",
            dataType: "json",
            success:function(data) {
                  $('select[name="sale_unit_id"]').empty();
                  $('select[name="purchase_unit_id"]').empty();
                  $.each(data, function(key, value) {
                      $('select[name="sale_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                      $('select[name="purchase_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                  });
                  $('.selectpicker').selectpicker('refresh');
            },
        });
    }

    $("input[name='is_batch']").on("change", function () {
        if ($(this).is(':checked')) {
            $("#variant-option").hide(300);
        }
        else
            $("#variant-option").show(300);
    });

    $("input[name='is_variant']").on("change", function () {
        if ($(this).is(':checked')) {
            $("#variant-section").show(300);
            $("#batch-option").hide(300);
        }
        else {
            $("#variant-section").hide(300);
            $("#batch-option").show(300);
        }
    });

    $("input[name='is_diffPrice']").on("change", function () {
        if ($(this).is(':checked')) {
            $("#diffPrice-section").show(300);
        }
        else
            $("#diffPrice-section").hide(300);
    });

    $("input[name='variant']").on("input", function () {
        if($("#code").val() == ''){
            $("input[name='variant']").val('');
            alert('Please fillup above information first.');
        }
        else if($(this).val().indexOf(',') > -1) {
            var variant_name = $(this).val().slice(0, -1);
            var item_code = variant_name+'-'+$("#code").val();
            var newRow = $("<tr>");
            var cols = '';
            cols += '<td style="cursor:grab"><i class="dripicons-view-apps"></i></td>';
            cols += '<td><input type="text" class="form-control" name="variant_name[]" value="' + variant_name + '" /></td>';
            cols += '<td><input type="text" class="form-control" name="item_code[]" value="'+item_code+'" /></td>';
            cols += '<td><input type="number" class="form-control" name="additional_price[]" value="" step="any" /></td>';
            cols += '<td><button type="button" class="vbtnDel btn btn-sm btn-danger">X</button></td>';

            $("input[name='variant']").val('');
            newRow.append(cols);
            $("table.variant-list tbody").append(newRow);
        }
    });

    //Delete variant
    $("table#variant-table tbody").on("click", ".vbtnDel", function(event) {
        $(this).closest("tr").remove();
    });

    $( "#promotion" ).on( "change", function() {
        if ($(this).is(':checked')) {
            $("#starting_date").val($.datepicker.formatDate('dd-mm-yy', new Date()));
            $("#promotion_price").show(300);
            $("#start_date").show(300);
            $("#last_date").show(300);
        }
        else {
            $("#promotion_price").hide(300);
            $("#start_date").hide(300);
            $("#last_date").hide(300);
        }
    });

    var starting_date = $('#starting_date');
    starting_date.datepicker({
     format: "dd-mm-yyyy",
     startDate: "<?php echo date('d-m-Y'); ?>",
     autoclose: true,
     todayHighlight: true
     });

    var ending_date = $('#ending_date');
    ending_date.datepicker({
     format: "dd-mm-yyyy",
     startDate: "<?php echo date('d-m-Y'); ?>",
     autoclose: true,
     todayHighlight: true
     });

    $(window).keydown(function(e){
        if (e.which == 13) {
            var $targ = $(e.target);

            if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
                var focusNext = false;
                $(this).find(":input:visible:not([disabled],[readonly]), a").each(function(){
                    if (this === e.target) {
                        focusNext = true;
                    }
                    else if (focusNext){
                        $(this).focus();
                        return false;
                    }
                });

                return false;
            }
        }
    });
    //dropzone portion
    Dropzone.autoDiscover = false;

    jQuery.validator.setDefaults({
        errorPlacement: function (error, element) {
            if(error.html() == 'Select Category...')
                error.html('This field is required.');
            $(element).closest('div.form-group').find('.validation-msg').html(error.html());
        },
        highlight: function (element) {
            $(element).closest('div.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('div.form-group').removeClass('has-error').addClass('has-success');
            $(element).closest('div.form-group').find('.validation-msg').html('');
        }
    });

    function validate() {
        var product_code = $("input[name='code']").val();
        var barcode_symbology = $('select[name="barcode_symbology"]').val();
        var exp = /^\d+$/;

        if(!(product_code.match(exp)) && (barcode_symbology == 'UPCA' || barcode_symbology == 'UPCE' || barcode_symbology == 'EAN8' || barcode_symbology == 'EAN13') ) {
            alert('Product code must be numeric.');
            return false;
        }
        else if(product_code.match(exp)) {
            if(barcode_symbology == 'UPCA' && product_code.length > 11){
                alert('Product code length must be less than 12');
                return false;
            }
            else if(barcode_symbology == 'EAN8' && product_code.length > 7){
                alert('Product code length must be less than 8');
                return false;
            }
            else if(barcode_symbology == 'EAN13' && product_code.length > 12){
                alert('Product code length must be less than 13');
                return false;
            }
        }

        if( $("#type").val() == 'combo' ) {
            var rownumber = $('table.order-list tbody tr:last').index();
            if (rownumber < 0) {
                alert("Please insert product to table!")
                return false;
            }
        }
        if($("#is-variant").is(":checked")) {
            rowindex = $("table#variant-table tbody tr:last").index();
            if (rowindex < 0) {
                alert('This product has variant. Please insert variant to table');
                return false;
            }
        }
        $("input[name='price']").prop('disabled',false);
        return true;
    }

    $("table#variant-table tbody").sortable({
        items: 'tr',
        cursor: 'grab',
        opacity: 0.5,
    });

    $(".dropzone").sortable({
        items:'.dz-preview',
        cursor: 'grab',
        opacity: 0.5,
        containment: '.dropzone',
        distance: 20,
        tolerance: 'pointer',
        stop: function () {
          var queue = myDropzone.getAcceptedFiles();
          newQueue = [];
          $('#imageUpload .dz-preview .dz-filename [data-dz-name]').each(function (count, el) {
                var name = el.innerHTML;
                queue.forEach(function(file) {
                    if (file.name === name) {
                        newQueue.push(file);
                    }
                });
          });
          myDropzone.files = newQueue;
        }
    });

    myDropzone = new Dropzone('div#imageUpload', {
        addRemoveLinks: true,
        autoProcessQueue: false,
        uploadMultiple: true,
        parallelUploads: 100,
        maxFilesize: 12,
        paramName: 'image',
        clickable: true,
        method: 'POST',
        url: '{{route('products.store')}}',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        renameFile: function(file) {
            var dt = new Date();
            var time = dt.getTime();
            return time + file.name;
        },
        acceptedFiles: "image/jpeg,image/jpg,image/png,image/gif,image/webp,.jpeg,.jpg,.png,.gif,.webp",
        dictDefaultMessage: "Drop images here or click — you can also paste (Ctrl+V / ⌘V)",
        init: function () {
            var myDropzone = this;
            $('#submit-btn').on("click", function (e) {
                e.preventDefault();
                $("#image-error").text('');
                $("#name-error").text('');
                $("#code-error").text('');
                if (window.tinyMCE && tinyMCE.triggerSave) {
                    try { tinyMCE.triggerSave(); } catch (err) {}
                }
                if ( $("#product-form").valid() && validate() ) {
                    if(myDropzone.getAcceptedFiles().length) {
                        myDropzone.processQueue();
                    }
                    else {
                        $.ajax({
                            type:'POST',
                            url:'{{route('products.store')}}',
                            data: $("#product-form").serialize(),
                            success:function(response){
                                location.href = '{{ route('products.index') }}';
                            },
                            error:function(response) {
                              showProductSaveError(response);
                            },
                        });
                    }
                } else {
                    var $err = $("#product-form .has-error:visible:first");
                    if ($err.length) {
                        $('html, body').animate({ scrollTop: $err.offset().top - 100 }, 200);
                    }
                }
            });

            this.on('sending', function (file, xhr, formData) {
                // Append all form inputs to the formData Dropzone will POST
                var data = $("#product-form").serializeArray();
                $.each(data, function (key, el) {
                    formData.append(el.name, el.value);
                });
            });
        },
        error: function (file, response) {
            var parsed = response;
            if (typeof response === 'string') {
                try { parsed = JSON.parse(response); } catch (err) { parsed = { message: response }; }
            }
            if (parsed && parsed.errors && parsed.errors.name) {
              $("#name-error").text(parsed.errors.name);
              this.removeAllFiles(true);
            }
            else if (parsed && parsed.errors && parsed.errors.code) {
              $("#code-error").text(parsed.errors.code);
              this.removeAllFiles(true);
            }
            var message = (parsed && parsed.message) ? parsed.message : (typeof response === 'string' ? response : 'Could not save the product.');
            $("#image-error").text(message);
            if (file && file.previewElement) {
                file.previewElement.classList.add("dz-error");
                var nodes = file.previewElement.querySelectorAll("[data-dz-errormessage]");
                for (var i = 0; i < nodes.length; i++) {
                    nodes[i].textContent = message;
                }
            }
        },
        successmultiple: function (file, response) {
            location.href = '{{ route('products.index') }}';
        },
        completemultiple: function (file, response) {
            console.log(file, response, "completemultiple");
        },
        reset: function () {
            console.log("resetFiles");
            this.removeAllFiles(true);
        }
    });

    function addPastedImageToDropzone(blob) {
        if (!blob || !blob.type || blob.type.indexOf('image') === -1 || typeof myDropzone === 'undefined') {
            return;
        }
        var ext = 'png';
        if (blob.type.indexOf('jpeg') !== -1 || blob.type.indexOf('jpg') !== -1) ext = 'jpg';
        else if (blob.type.indexOf('gif') !== -1) ext = 'gif';
        else if (blob.type.indexOf('webp') !== -1) ext = 'webp';
        var pasteFile = new File([blob], 'pasted-' + Date.now() + '.' + ext, { type: blob.type });
        myDropzone.addFile(pasteFile);
    }

    $(document).on('paste', function(e) {
        var clip = e.clipboardData || (e.originalEvent && e.originalEvent.clipboardData);
        if (!clip || !clip.items) {
            return;
        }
        for (var i = 0; i < clip.items.length; i++) {
            var item = clip.items[i];
            if (item.kind === 'file') {
                var blob = item.getAsFile();
                if (blob && blob.type && blob.type.indexOf('image') !== -1) {
                    e.preventDefault();
                    addPastedImageToDropzone(blob);
                }
            }
        }
    });

    function showProductSaveError(xhr) {
        var msg = 'Could not save the product.';
        var json = xhr && xhr.responseJSON;
        if (json) {
            if (json.message) {
                msg = json.message;
            }
            if (json.errors) {
                if (json.errors.name) {
                    $("#name-error").text(json.errors.name);
                }
                if (json.errors.code) {
                    $("#code-error").text(json.errors.code);
                }
                var first = json.errors.name || json.errors.code;
                if (first) {
                    msg = $.isArray(first) ? first[0] : first;
                }
            }
        }
        $("#image-error").text(msg);
        alert(msg);
    }

</script>
@endsection
