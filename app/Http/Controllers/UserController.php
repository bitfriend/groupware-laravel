<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $company_uuid = $request->query('company_uuid');
        $department_uuid = $request->query('department_uuid');
        $search = $request->query('search');
        $sort_by = $request->query('sort_by');
        $limit = $request->query('limit');

        $query = ['MATCH (u:User)'];
        if (!empty($search))
            $query[] = 'WHERE u.name CONTAINS {search}';
        $query[] = 'RETURN u';
        if (!empty($limit))
            $query[] = 'LIMIT {limit}';

        $records = $this->client->run(implode(' ', $query), [
            'search' => $search,
            'limit' => intval($limit),
        ])->getRecords();
        $result = [];
        foreach ($records as $record) {
            $user = $record->get('u');
            $data = $user->values();
            if (isset($data['password']))
                unset($data['password']);
            $result[] = $data;
        }
        return $result;
    }

    public function show($uuid)
    {
        return $this->getNode($uuid);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);
        $validator->after(function ($validator) use ($request) {
            if (!$validator->errors()->has('email')) {
                if (!$this->checkUnique('User', 'email', $request->input('email')))
                    $validator->errors()->add('email', 'This email was registered already');
            }
        });
        if ($validator->fails()) {
            return [
                'success' => FALSE,
                'errors' => $validator->messages(),
            ];
        }

        $data = $request->only(['name', 'email', 'password']);
        $data['password'] = Hash::make($data['password']);
        $node = $this->createNode('User', $data);
        return $node->values();
    }

    public function update(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'email',
            'password' => 'min:6',
        ]);
        $validator->after(function ($validator) use ($request, $uuid) {
            if (!$validator->errors()->has('email')) {
                if (!$this->checkUnique('User', 'email', $request->input('email'), $uuid))
                    $validator->errors()->add('email', 'This email was registered already');
            }
        });
        if ($validator->fails()) {
            return [
                'success' => FALSE,
                'errors' => $validator->messages(),
            ];
        }

        $data = $request->only(['name', 'email', 'password']);
        if (empty($data))
            $node = $this->getNode($uuid);
        else
            $node = $this->updaetNode($uuid, $data);
        return $node->values();
    }

    public function delete(Request $request, $uuid)
    {
        if ($request->boolean('permanent')) {
        } else {
        }
    }
}
