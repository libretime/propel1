#!/usr/bin/env bash
# Reset Propel tests fixtures
# 2011 - William Durand <william.durand1@gmail.com>

CURRENT=$(pwd)

function rebuild {
    local dir=$1

    echo "[ $dir ]"

    if [ -d "$dir/build" ]; then
        rm -rf "$dir/build"
    fi

    $ROOT/generator/bin/propel-gen $FIXTURES_DIR/$dir main >/dev/null
    $ROOT/generator/bin/propel-gen $FIXTURES_DIR/$dir insert-sql >/dev/null
}

FIXTURES_DIR=""

if [ -d "$CURRENT/fixtures" ]; then
    ROOT=".."
    FIXTURES_DIR="$CURRENT/fixtures"
elif [ -d "$CURRENT/test/fixtures" ]; then
    ROOT="."
    FIXTURES_DIR="$CURRENT/test/fixtures"
else
    echo "ERROR: No 'test/fixtures/' directory found !"
    exit 1
fi

DIRS=$(ls $FIXTURES_DIR)

# "namespaced" targets the same physical tables (book, author, media, ...) in
# the same "test" database as "bookstore" - just under different PHP
# namespaces for the generated classes - and its DDL drops and recreates
# those tables. Under PostgreSQL that DROP has to cascade (there's no
# equivalent of MySQL's FOREIGN_KEY_CHECKS=0 to suppress it), which also
# drops foreign key constraints that other bookstore tables (e.g. media,
# review) hold on the dropped table. Building "bookstore" last makes sure
# its own constraints are the ones left standing afterwards.
for dir in $DIRS; do
    if [ "$dir" = "bookstore" ]; then
        continue
    fi
    rebuild $dir
done
rebuild bookstore

# Special case for reverse fixtures

REVERSE_DIRS=$(ls $FIXTURES_DIR/reverse)

for dir in $REVERSE_DIRS; do
    # The mysql reverse-engineering fixture needs a live MySQL server to build
    # against, which is no longer part of the test infrastructure (PostgreSQL
    # is used instead) - MysqlSchemaParserTest is skipped accordingly.
    if [ "$dir" = "mysql" ]; then
        continue
    fi

    if [ -f "$FIXTURES_DIR/reverse/$dir/build.properties" ]; then
        echo "[ $dir ]"
        $ROOT/generator/bin/propel-gen $FIXTURES_DIR/reverse/$dir insert-sql >/dev/null
    fi
done
