@extends('errors.layout')

@section('title', 'Sin acceso')
@section('code', '403')
@section('heading', 'Esta puerta no es para ti.')
@section('message', 'Tu cuenta no tiene permiso para ver esta sección. Si crees que es un error, habla con administración.')
@section('illustration', asset('media/gif/acceso-denegado.gif'))
