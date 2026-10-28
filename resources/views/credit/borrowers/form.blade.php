@php $editing = $borrower->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit borrower' : 'New borrower'">
    <x-page-header :title="$editing ? 'Edit '.$borrower->full_name : 'Register borrower'" :back="$editing ? route('credit.borrowers.show', $borrower) : route('credit.borrowers.index')" />
    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('credit.borrowers.update', $borrower) : route('credit.borrowers.store') }}" class="space-y-6">
        @csrf @if($editing) @method('PUT') @endif
        @include('credit.borrowers._fields')
        <div class="flex justify-end gap-2">
            <a href="{{ route('credit.borrowers.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save changes' : 'Register borrower' }}</button>
        </div>
    </form>
</x-layouts.app>
