<div class="w-full">
    @php
        $actions = $state ?? collect();
    @endphp

    @if($actions->isNotEmpty())
        <table class="w-full text-sm">
            <tbody>
                @foreach($actions as $action)
                    <tr class="border-b last:border-b-0">
                        <td class="px-4 py-2">
                            <div class="flex items-center space-x-2">
                                <span>
                                    {{ $action->Action_Level }}
                                </span>
                                @if($action->Action_Taken === 'Completed')
                                    <span class="text-xs text-green-700 bg-green-50 px-1.5 py-0.5 rounded">
                                        Completed
                                    </span>
                                @elseif($action->Action_Taken === 'Pending Clock')
                                    <span class="text-xs text-yellow-700 bg-yellow-50 px-1.5 py-0.5 rounded">
                                        Pending Clock
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right text-gray-500">
                            {{ $action->Action_By }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-center text-gray-500 py-4">
            No actions recorded
        </div>
    @endif
</div>