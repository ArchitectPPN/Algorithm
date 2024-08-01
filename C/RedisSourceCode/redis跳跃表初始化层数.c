#include <stdio.h>
#include <stdlib.h>
#include <math.h>

/* Skiplist P = 1/4 */
#define ZSKIPLIST_P 0.25
#define ZSKIPLIST_MAXLEVEL 64


int zslRandomLevel(void) {
    int level = 1;
    while ((random()&0xFFFF) < (ZSKIPLIST_P * 0xFFFF))
        level += 1;
    return (level<ZSKIPLIST_MAXLEVEL) ? level : ZSKIPLIST_MAXLEVEL;
}

int main() {
    int level = zslRandomLevel();

    printf("生成的level: %d", level);

    return 0;
}