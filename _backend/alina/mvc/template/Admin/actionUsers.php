<?php

use alina\mvc\view\html as htmlAlias;

/** @var $data stdClass */
$i = 0;
?>
<h1>User Manager</h1>
<?= (new htmlAlias)->piece('_system/html/_form/table002.php', [$data->pagination]) ?>

<div class="table-responsive">
    <table class="table table-striped table-hover  table-dark">
        <?php foreach ($data->users as $mUser) { ?>
            <tr>
                <td><?= ++$i ?></td>
                <td>
                    <div>
                        <?php
                        $formRolesId = "form-user-{$mUser->id}-role";
                        ?>
                        <form action="" method="post" id="<?= $formRolesId ?>">
                            <input type="hidden" name="form_id" value="<?= $formRolesId ?>"/>
                            <input type="hidden" name="id" value="<?= $mUser->id ?>"/>
                            <select name="role_ids[]" multiple class="form-control">
                                <?php foreach ($data->roles as $role) { ?>
                                    <option
                                            value="<?= $role->id ?>"
                                        <?= (in_array($role->id, array_column($mUser->rbac_user_role, 'id'))) ? 'selected' : '' ?>
                                    ><?= $role->name ?></option>
                                <?php } ?>
                            </select>
                            <button form="<?= $formRolesId ?>" type="submit" name="action" value="set-roles" class="btn btn-primary w-100">Set Roles</button>
                        </form>
                    </div>
                </td>
                <td>
                    <a href="/#/auth/profile/<?= $mUser->id ?>">
                        <?php if ($mUser->emblem) { ?>
                            <img src="<?= $mUser->emblem ?>" alt="" width="100">
                        <?php } else { ?>
                            <img src="undefined" alt="NO AVATAR" width="100">
                        <?php } ?>
                    </a>
                </td>
                <?php
                $formId = "form-user-{$mUser->id}";
                ?>
                <td>
                    <form
                            id="<?= $formId ?>"
                            action=""
                            method="post"
                    >
                        <input type="hidden" name="form_id" value="<?= $formId ?>"/>
                        <input type="hidden" name="id" value="<?= $mUser->id ?>"/>
                        <?= $mUser->id ?>
                    </form>
                </td>
                <td><input form="<?= $formId ?>" type="text" class="form-control" name="mail" value="<?= $mUser->mail ?>"/></td>
                <td><input form="<?= $formId ?>" type="text" class="form-control" name="firstname" value="<?= $mUser->firstname ?>"/></td>
                <td><input form="<?= $formId ?>" type="text" class="form-control" name="lastname" value="<?= $mUser->lastname ?>"/></td>
                <td><input form="<?= $formId ?>" type="text" class="form-control" name="birth" value="<?= $mUser->birth ?>"/></td>
                <td>
                    <button
                            form="<?= $formId ?>" type="submit" name="action" value="update" class="btn btn-primary"
                            onclick="return confirm('Are you sure?')"
                    >Save
                    </button>
                    <button
                            form="<?= $formId ?>" type="submit" name="action" value="delete" class="btn btn-danger"
                            onclick="return confirm('Are you sure?')"
                    >Delete
                    </button>
                </td>

            </tr>
        <?php } ?>
    </table>
</div>