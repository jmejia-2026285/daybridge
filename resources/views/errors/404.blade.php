@extends('errors.layout')

@section('title', 'Página no encontrada')
@section('code', '404')
@section('heading', 'Aquí no hay nada.')
@section('message', 'La página que buscas cambió de lugar o nunca existió. Vuelve al inicio y retoma el hilo.')
@section('illustration', asset('media/gif/not_found.gif'))
