@extends('layouts.app')

@section('body')
    <div class="h-full font-sans">
        <div class="h-full flex justify-center">
            <div class="w-full">
                <h1 class="text-2xl text-indigo-darker font-bold my-10 text-center">
                    <div class="no-underline hover:underline cursor-default text-indigo-darker">
                        {{ config('app.name', 'Laravel') }}
                    </div>
                </h1>

                <div class="container mx-auto flex flex-col flex-wrap justify-center items-center">
                    <div class="text-grey-darker">
                        Select an image you want to generate a patterns for.
                    </div>
                    <form ref="uploadForm" action="{{ route('generate') }}" method="POST" enctype="multipart/form-data" class="text-center">
                        @csrf
                        <div class="overflow-hidden relative w-64 mt-4 mb-4">
                            <button class="bg-indigo hover:bg-indigo-light text-white font-bold py-2 px-4 border-b-4 border-indigo-dark hover:border-indigo rounded">
                                Select png file
                            </button>
                            <input class="cursor-pointer absolute block opacity-0 pin-r pin-t" type="file" name="image" @change="submitUploadForm">
                        </div>
                        <div v-if="loading" class="text-grey-dark">
                            Please wait... You will be redirected when your image is ready.
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection

