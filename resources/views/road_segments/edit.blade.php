@extends('layouts.app')

@section('content')
<h1>Edit Road Segment #{{ $roadSegment->id }}</h1>

<form method="POST" action="{{ route('road-segments.update', $roadSegment) }}">
    @csrf
    @method('PATCH')
    
    <label>Lat1:</label>
    <input type="text" name="lat1" value="{{ old('lat1', $roadSegment->lat1) }}">
    
    <label>Lon1:</label>
    <input type="text" name="lon1" value="{{ old('lon1', $roadSegment->lon1) }}">
    
    <label>Lat2:</label>
    <input type="text" name="lat2" value="{{ old('lat2', $roadSegment->lat2) }}">
    
    <label>Lon2:</label>
    <input type="text" name="lon2" value="{{ old('lon2', $roadSegment->lon2) }}">
    
    <label>Max Speed:</label>
    <input type="number" name="maxspeed" value="{{ old('maxspeed', $roadSegment->maxspeed) }}">
    
    <button type="submit">Save</button>
</form>
@endsection
