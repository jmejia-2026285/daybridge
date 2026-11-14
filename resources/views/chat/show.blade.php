@extends('layouts.app')

@section('content')
@livewire('chat.show', ['conversation' => $conversation])
@endsection
