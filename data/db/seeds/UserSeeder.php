<?php

use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed
{

    public function run()
    {
        $faker = Faker\Factory::create();
        $data = [];
        $flush = 32;
        $max = 512;
        $statuses = [
            'suspended', // we closed account
            'closed', // we closed account
            'cancelled', // user cancelled account
            'registered', // initial registration
            'confirmed', // user has confirmed email address - needed for api access
        ];
        $groups = ['admin', 'user', 'api', 'user,api', 'admin,user,api'];
        for ($i = 0; $i < $max; $i++) {
            $data[] = [
                'uuid' => $faker->uuid,
                'password' => substr(sha1($faker->password),0,15),
                'email' => $faker->email,
                'firstname' => $faker->firstName,
                'lastname' => $faker->lastName,
                'groups' => $groups[rand(0,4)],
                'status' => $statuses[rand(0,4)],
                'password_question' => $faker->sentence(10, true),
                'password_answer' => $faker->sentence(3),
                'created' => $faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
                'login_last' => $faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
                'login_count' => rand(1, 1000)
            ];
            // flush data to database
            if (count($data) > $flush) {
                $this->insert('users', $data);
                $data = [];
            }
        }
        if (count($data) > $flush) {
            $this->insert('users', $data);
            $data = [];
        }
    }

}
