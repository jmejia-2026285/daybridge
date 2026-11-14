@extends('errors.layout')

@section('title', 'Sesión expirada')
@section('code', '419')
@section('heading', 'Tu sesión tomó un respiro.')
@section('message', 'Por seguridad, la página caducó. Recarga para continuar donde ibas.')
@section('illustration', asset('media/gif/warning.gif'))
