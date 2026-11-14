@extends('errors.layout')

@section('title', 'Error del servidor')
@section('code', '500')
@section('heading', 'Algo se cruzó en el puente.')
@section('message', 'Nuestro equipo ya está revisando lo que pasó. Intenta de nuevo en unos segundos.')
@section('illustration', asset('media/gif/mantenimiento-laptop.gif'))
