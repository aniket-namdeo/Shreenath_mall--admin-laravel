@empty(!$list)

@php $imgUrl = ($details->image != "" && $details->image != null) ? $details->image : "uploads/default.jpg"; @endphp


<div class="row">
    <div class="col-lg-12">
        @include('backend.layouts.alert')

        <form action="{{ route('update-brand.update', $details->id); }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card h-auto">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-2">
                            <label class="form-label" for="">Name</label>
                            <input type="text" class="form-control" name="name" onkeypress="return /[A-Za-z ]/i.test(event.key)" value="{{ $details->name }}" />
                        </div>

                        <div class="col-md-full mb-2">
                            <label class="form-label" for="">Description</label>
                            <textarea class="form-control" name="description">{{ $details->description }}</textarea>
                        </div>
                       
                        <div class="col-md-4 form-group mb-3">
                            <label for="logo"> Logo </label>
                            <input type="file" name="logo" class="form-control" onchange="readURL(this);" />
                        </div>
                        <div class="col-md-8 mb-3">
                            <img alt="Plan logo" src="{{ asset('uploads/default.jpg');  }}" class="img-responsive rounded" width="100" height="auto" id="img_preview" />
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn web-btn mt-3" id="submit_btn">
                                Update
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endempty