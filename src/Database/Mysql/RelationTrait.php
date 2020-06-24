<?php

namespace One\Database\Mysql;


trait RelationTrait
{
    /**
     * @param string $self_column
     * @param string $remote
     * @param string $remote_column
     * @return Model
     */
    protected function hasOne($self_column, $remote, $remote_column)
    {
        return new HasOne($self_column, $remote, $remote_column, $this);
    }

    /**
     * @param string $self_column
     * @param string $remote
     * @param string $remote_column
     * @return Model
     */
    protected function hasMany($self_column, $remote, $remote_column)
    {
        return new HasMany($self_column, $remote, $remote_column, $this);
    }

    /**
     * @param string $self_column
     * @param string $remote
     * @param string $remote_column
     * @return Model
     */
    protected function hasIn($self_column, $remote, $remote_column)
    {
        return new HasIn($self_column, $remote, $remote_column, $this);
    }

    /**
     * @param array $remote_type [$self_type => $remote_model_class]
     * @param array $remote_type_id [$self_type => $remote_table_rel_id]
     * @param string $self_type
     * @param string $self_id
     * @return MorphOne
     */
    protected function morphOne(array $remote_type, array $remote_type_id, $self_type, $self_id)
    {
        return new MorphOne($remote_type, $remote_type_id, $self_type, $self_id, $this);
    }

    /**
     * @param array $remote_type [$self_type => $remote_model_class]
     * @param array $remote_type_id [$self_type => $remote_table_rel_id]
     * @param string $self_type
     * @param string $self_id
     * @return MorphMany
     */
    protected function morphMany(array $remote_type, array $remote_type_id, $self_type, $self_id)
    {
        return new MorphMany($remote_type, $remote_type_id, $self_type, $self_id, $this);
    }


}