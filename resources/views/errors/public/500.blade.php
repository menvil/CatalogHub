@extends('errors.public.layout')

@section('title', 'Internal server error')
@section('status', '500')
@section('heading', 'Something went wrong')
@section('message', 'The service could not complete this request. Please try again later.')
