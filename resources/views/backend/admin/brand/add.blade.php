<div class="row">
    <div class="col-lg-12">
        @include('backend.layouts.alert')

        <form action="{{ route('add-brand.store'); }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card h-auto">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-2">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label for="logo">logo</label>
                            <input type="file" class="form-control" id="logo" name="logo">
                        </div>
                        <div class="col-md-full mb-2">
                            <label class="form-label" for="">description</label>
                            <textarea class="form-control" name="description" value="{{ old('description') }}">
                            </textarea>
                        </div>


                        <div class="col-md-12">
                            <button type="submit" class="btn web-btn mt-3" id="submit_btn">
                                Submit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>