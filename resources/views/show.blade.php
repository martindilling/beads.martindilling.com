@extends('layouts.app')

@section('body')
    <div class="w-full h-full font-sans flex flex-col justify-start">
        <h1 class="text-2xl text-indigo-darker font-bold my-10 text-center">
            <a href="{{ route('home') }}" class="no-underline hover:underline text-indigo-darker hover:text-indigo-dark">
                Generate new
            </a>
        </h1>
        <div class="text-sm text-grey-dark text-center mb-3">
            Image is resized, saving it will save the full size.
        </div>

        <div class="container mx-auto flex justify-center items-start">
            <img class="w-full" src="{{ $image }}" alt="">
        </div>
    </div>
@endsection

