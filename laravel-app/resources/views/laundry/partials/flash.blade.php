@if(session('message'))
    <div class="alert alert-success">{{ session('message') }}</div>
@endif
@if(session('not_permitted'))
    <div class="alert alert-danger">{{ session('not_permitted') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    </div>
@endif
