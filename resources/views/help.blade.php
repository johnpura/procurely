@extends('layouts.app')

@section('title', 'Help')

@section('content')
    @php
        $isAdmin = auth()->user()->isAdmin();
        $card = 'rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]';
        $h = 'text-lg font-semibold text-gray-800 dark:text-white/90';
        $p = 'mt-2 text-sm text-gray-700 dark:text-gray-300';
        $ul = 'mt-2 list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300';
        $sections = [
            'basics' => ['The basics', false],
            'dashboard' => ['Your dashboard', false],
            'draft' => ['Drafting a bid', false],
            'documents' => ['Adding documents', false],
            'publish' => ['Assigning the contact and publishing', true],
            'responses' => ['Responses before the deadline', false],
            'opening' => ['Opening responses after the deadline', false],
            'award' => ['Awarding or cancelling a bid', true],
            'deadlines' => ['Changing a deadline', true],
            'people' => ['Managing people', true],
            'audit' => ['The audit log', true],
            'account' => ['Your profile and password', false],
            'faq' => ['Common questions', false],
        ];
    @endphp

    <div class="max-w-3xl space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Help</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">How to run a bid from draft to award.{{ $isAdmin ? '' : ' Sections for administrators are hidden.' }}</p>
        </div>

        <nav class="{{ $card }}" aria-label="Help contents">
            <ul class="grid gap-1 text-sm sm:grid-cols-2">
                @foreach ($sections as $id => [$title, $adminOnly])
                    @if ($isAdmin || ! $adminOnly)
                        <li><a href="#{{ $id }}" class="text-brand-500 hover:text-brand-600">{{ $title }}</a>@if ($adminOnly) <span class="text-xs text-gray-500">(admins)</span>@endif</li>
                    @endif
                @endforeach
            </ul>
        </nav>

        <section id="basics" class="{{ $card }}">
            <h3 class="{{ $h }}">The basics</h3>
            <p class="{{ $p }}">Procurely has two kinds of people. <strong>Staff</strong> prepare bids. <strong>Admins</strong> publish them, open and award them, and manage people. Vendors never sign in; they use the public bid pages.</p>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-left text-sm text-gray-700 dark:text-gray-300">
                    <caption class="sr-only">What each role can do</caption>
                    <thead><tr class="border-b border-gray-200 dark:border-gray-800"><th scope="col" class="py-2 pr-4 font-medium">Action</th><th scope="col" class="py-2 pr-4 font-medium">Staff</th><th scope="col" class="py-2 font-medium">Admin</th></tr></thead>
                    <tbody>
                        @foreach ([
                            ['Create a draft bid', 'Yes', 'Yes'],
                            ['Edit a bid and its documents', 'Own drafts only', 'Any bid'],
                            ['Delete a bid', 'Own drafts only', 'Drafts only'],
                            ['Assign the public contact', 'No', 'Yes'],
                            ['Publish, cancel or award', 'No', 'Yes'],
                            ['See how many responses arrived', 'If assigned', 'Yes'],
                            ['Open responses after the deadline', 'If assigned', 'Yes'],
                            ['Manage people and read the audit log', 'No', 'Yes'],
                        ] as [$action, $staff, $admin])
                            <tr class="border-b border-gray-100 dark:border-gray-800"><th scope="row" class="py-2 pr-4 font-normal">{{ $action }}</th><td class="py-2 pr-4">{{ $staff }}</td><td class="py-2">{{ $admin }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section id="dashboard" class="{{ $card }}">
            <h3 class="{{ $h }}">Your dashboard</h3>
            <p class="{{ $p }}">The Dashboard shows what needs attention. {{ $isAdmin ? 'As an admin you see every bid:' : 'As staff you see your own drafts and the bids assigned to you:' }}</p>
            <ul class="{{ $ul }}">
                <li>Drafts waiting to be finished{{ $isAdmin ? ' or published' : '' }}.</li>
                <li>Bids closing in the next 7 days, with how many responses have arrived.</li>
                <li>Closed bids waiting {{ $isAdmin ? 'for an award' : 'for your review' }}, with a link to open their responses.</li>
                @if ($isAdmin)<li>A warning when an open bid has no assigned contact, and the latest audit activity.</li>@endif
            </ul>
        </section>

        <section id="draft" class="{{ $card }}">
            <h3 class="{{ $h }}">Drafting a bid</h3>
            <ol class="{{ str_replace('list-disc', 'list-decimal', $ul) }}">
                <li>Open <strong>Bids</strong>, then <strong>New bid</strong>.</li>
                <li>Enter a title, department, description and the closing date and time. Times are in {{ config('app.timezone') }}.</li>
                <li>Leave the reference number blank to get the next one automatically (for example BID-{{ now()->year }}-0001).</li>
                <li>Save. The bid is a <strong>draft</strong>: vendors cannot see it.</li>
                <li>Use <strong>Preview public page</strong> to see what vendors will see.</li>
            </ol>
            <p class="{{ $p }}">You can edit or delete your own drafts as often as you like. Once a bid is published, only admins can change it.</p>
        </section>

        <section id="documents" class="{{ $card }}">
            <h3 class="{{ $h }}">Adding documents</h3>
            <p class="{{ $p }}">On the bid page, use <strong>Documents</strong> to upload PDFs such as the RFP packet or an addendum (20 MB maximum each). Drafts' documents are private; documents on a published bid can be downloaded by anyone. Admins can add addenda after publishing.</p>
        </section>

        @if ($isAdmin)
            <section id="publish" class="{{ $card }}">
                <h3 class="{{ $h }}">Assigning the contact and publishing</h3>
                <ul class="{{ $ul }}">
                    <li>Choose the <strong>Public contact</strong>. Vendors see that person's name, job title, email and phone. With none chosen, they see the department's default contact.</li>
                    <li>Check the closing time. It must be in the future before you can publish.</li>
                    <li>Click <strong>Publish</strong>. The reference number is then locked and the bid appears on the Open bids page.</li>
                </ul>
                <p class="{{ $p }}">If the assigned person is disabled or removed, the bid falls back to the default contact automatically.</p>
            </section>
        @endif

        <section id="responses" class="{{ $card }}">
            <h3 class="{{ $h }}">Responses before the deadline</h3>
            <p class="{{ $p }}">Vendors can respond online (PDF files, up to 5 of 20 MB each), by email, by mail or in person. Online responses give the vendor a receipt code and an email.</p>
            <ul class="{{ $ul }}">
                <li>Before the deadline responses are <strong>sealed</strong>. {{ $isAdmin ? 'You and the assigned contact' : 'If you are the assigned contact, you' }} can see only how many have arrived.</li>
                <li>To record a response that came by email, mail or in person, open the bid, then <strong>Log a response</strong>. Enter the date and time it was actually received. Anything received after the deadline is marked <strong>Late</strong>.</li>
            </ul>
        </section>

        <section id="opening" class="{{ $card }}">
            <h3 class="{{ $h }}">Opening responses after the deadline</h3>
            <p class="{{ $p }}">When the closing time passes, {{ $isAdmin ? 'admins and the assigned contact' : 'the assigned contact (and admins)' }} can open the responses from the bid page or the dashboard.</p>
            <ul class="{{ $ul }}">
                <li>Each list view, each response and each file download is written to the audit log with your name and the time.</li>
                <li>Late responses are flagged and cannot be awarded.</li>
                <li>Everyone else, including other staff, gets a "not allowed" page.</li>
            </ul>
        </section>

        @if ($isAdmin)
            <section id="award" class="{{ $card }}">
                <h3 class="{{ $h }}">Awarding or cancelling a bid</h3>
                <ul class="{{ $ul }}">
                    <li><strong>Award:</strong> after the deadline, open the bid and use <strong>Record award</strong>. Pick the vendor from those with on-time responses (or type another name), add the amount and date. The result shows on the public Closed bids page.</li>
                    <li><strong>Cancel:</strong> a published bid can be cancelled. Vendors then see it as cancelled.</li>
                    <li>Published bids can never be deleted, only cancelled or awarded.</li>
                </ul>
            </section>

            <section id="deadlines" class="{{ $card }}">
                <h3 class="{{ $h }}">Changing a deadline</h3>
                <p class="{{ $p }}">Edit the closing time on the bid page. The change is recorded in the audit log with the old and new values. Moving the deadline of an already closed bid into the future <strong>seals its responses again</strong> until the new time passes, so think before extending a bid that has been opened.</p>
            </section>

            <section id="people" class="{{ $card }}">
                <h3 class="{{ $h }}">Managing people</h3>
                <ul class="{{ $ul }}">
                    <li><strong>Add user</strong> creates an account. There is no self sign-up.</li>
                    <li><strong>Edit</strong> changes name, email, phone, job title and role.</li>
                    <li><strong>Send password reset link</strong> emails the person a link to choose a new password.</li>
                    <li><strong>Disable</strong> is temporary (leave or suspension): they are signed out and cannot sign in until enabled.</li>
                    <li><strong>Remove</strong> is for someone who has left. They move to the Former tab, history stays, and you can restore them.</li>
                </ul>
                <p class="{{ $p }}">The system will not let you remove, disable or demote the last active admin, or disable or remove yourself.</p>
            </section>

            <section id="audit" class="{{ $card }}">
                <h3 class="{{ $h }}">The audit log</h3>
                <p class="{{ $p }}">The Audit log lists who did what and when: bid changes (with before and after values), publishing and awards, every opening of responses, and all changes to people. Entries cannot be edited or deleted. Filter by date, person, action or bid reference, or use the History link on a bid.</p>
            </section>
        @endif

        <section id="account" class="{{ $card }}">
            <h3 class="{{ $h }}">Your profile and password</h3>
            <p class="{{ $p }}">Open <strong>Profile</strong> to change your name, email or password. Passwords need at least 12 characters with upper and lower case letters and numbers. Sessions end after {{ config('session.lifetime') }} minutes of inactivity.</p>
        </section>

        <section id="faq" class="{{ $card }}">
            <h3 class="{{ $h }}">Common questions</h3>
            <dl class="mt-2 space-y-3 text-sm text-gray-700 dark:text-gray-300">
                <div><dt class="font-medium">Why can't I open the responses?</dt><dd>Either the deadline has not passed yet, or you are not an admin or the assigned contact for that bid.</dd></div>
                <div><dt class="font-medium">Why can't I edit a bid?</dt><dd>Staff can edit only their own drafts. Ask an admin to change a published bid.</dd></div>
                <div><dt class="font-medium">A vendor says they did not get a receipt email.</dt><dd>Their response was still saved. Ask them for the receipt code shown on screen, and tell an admin so the mail settings can be checked.</dd></div>
                <div><dt class="font-medium">A vendor emailed a response after the deadline.</dt><dd>Log it with the real received time. It will be marked Late and cannot be awarded.</dd></div>
                <div><dt class="font-medium">I can't sign in.</dt><dd>Wait a few minutes after repeated wrong passwords. If your account is disabled, ask an admin.</dd></div>
            </dl>
        </section>
    </div>
@endsection
