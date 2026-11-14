@extends('errors.layout')

@section('title', 'Demasiadas solicitudes')
@section('code', '429')
@section('heading', 'Vamos un poco más despacio.')
@section('message', 'Recibimos demasiadas solicitudes en muy poco tiempo. Espera un momento y vuelve a intentarlo.')
@section('illustration', asset('media/gif/warning.gif'))
